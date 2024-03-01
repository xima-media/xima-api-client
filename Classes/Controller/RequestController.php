<?php

declare(strict_types=1);

namespace Xima\XimaApiClient\Controller;

use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LogLevel;
use TYPO3\CMS\Backend\Attribute\Controller;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Pagination\SlidingWindowPagination;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\Controller\Exception\RequiredArgumentMissingException;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Fluid\View\TemplateView;
use TYPO3\CMS\Frontend\DataProcessing\SiteProcessor;
use Xima\XimaApiClient\ApiClient\ApiClient;
use Xima\XimaApiClient\ApiClient\ApiSchema;
use Xima\XimaApiClient\Configuration;
use Xima\XimaApiClient\Domain\Model\ReusableRequest;
use Xima\XimaApiClient\Domain\Repository\ReusableRequestRepository;
use Xima\XimaApiClient\Event\ModifyRequestFiltersEvent;
use Xima\XimaApiClient\Helper\CacheHelper;
use Xima\XimaApiClient\Helper\RequestHelper;
use Xima\XimaApiClient\Pagination\RequestPaginator;
use Xima\XimaApiClient\Service\FilterService;

#[Controller]
class RequestController extends ActionController
{
    public function __construct(
        protected ApiSchema $apiSchema,
        protected ApiClient $apiClient,
        protected ReusableRequestRepository $reusableRequestRepository,
        protected FilterService $filterService,
        protected CacheHelper $cacheHelper,
        protected RequestHelper $requestHelper,
        protected ExtensionConfiguration $extensionConfiguration
    ) {
    }

    public function reusableRequestAction(): ResponseInterface
    {
        $data = $this->configurationManager->getContentObject()?->data;

        $view = $this->prepareStandaloneView(
            $data['tx_ximaapiclient_template'],
            (int)$data['tx_ximaapiclient_request'],
            [
                'parameterOverrides' => $this->requestHelper->getParameterOverrides($data['uid']),
            ]
        );

        return $this->htmlResponse($view->render());
    }

    public function reusableRequestAjaxAction(): ResponseInterface
    {
        if ($this->request->hasArgument('reusableRequestUid')) {
            $reusableRequest = $this->reusableRequestRepository->findByUid((int)$this->request->getArgument('reusableRequestUid'));
        } elseif ($this->request->hasArgument('reusableRequestPreparedEndpoint')) {
            $reusableRequest = $this->reusableRequestRepository->findByPreparedEndpoint($this->request->getArgument('reusableRequestPreparedEndpoint'));
        } else {
            throw new RequiredArgumentMissingException('Neither the parameter "reusableRequestUid" nor the parameter "reusableRequestPreparedEndpoint" was provided.');
        }

        if (!$reusableRequest) {
            throw new \Exception('Reusable request was not found');
        }

        $this->apiClient->init($reusableRequest->getClientAlias());
        $page = $this->request->getAttribute('routing')->getPageId();

        // important: dispatch event here so that parameter overrides have a higher priority
        $this->eventDispatcher->dispatch(new ModifyRequestFiltersEvent(
            $reusableRequest,
            $page
        ));

        // resolve xac_ get parameter
        $additionalGetParameters = $this->resolveAdditionalGetParameters();
        foreach ($additionalGetParameters as $key => $parameter) {
            $this->filterService->addFilter(
                $reusableRequest,
                name: $key,
                page: $page,
                value: $parameter
            );
        }

        // resolve post request body parameter
        if ($requestBodyRaw = $this->request->getBody()->getContents()) {
            $requestBody = json_decode($requestBodyRaw, true, 512, JSON_THROW_ON_ERROR);

            if (array_key_exists('overrideParameters', $requestBody)) {
                foreach ($requestBody['overrideParameters'] as $key => $parameter) {
                    $this->filterService->addFilter(
                        $reusableRequest,
                        name: $key,
                        page: $page,
                        value: $parameter
                    );
                }
            }
        }

        $arguments = [
            'page' => $page,
        ];

        $this->resolveArgumentsContext($arguments, $reusableRequest, true);

        return new JsonResponse($arguments);
    }

    /**
     * Prepares a StandalonwView object to render afterwards
     *
     * @param string $templateName The template to render
     * @param int $requestUid The reusable request
     * @param array $options Options to customize the view
     * @throws GuzzleException
     * @throws InvalidConfigurationTypeException
     */
    protected function prepareStandaloneView(string $templateName, int $requestUid, array $options = []): StandaloneView
    {
        $parameterOverrides = $options['parameterOverrides'] ?? false;
        $additionalData = $options['additionalData'] ?? [];

        $typoScript = GeneralUtility::makeInstance(ConfigurationManager::class)->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK,
            Configuration::EXT_NAME,
            Configuration::PLUGIN_NAME
        );
        $template = GeneralUtility::getFileAbsFileName($templateName);
        $cteData = $this->configurationManager->getContentObject()?->data;

        $cteData = array_replace_recursive($cteData, $additionalData);

        // preparing view
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setFormat('html');
        $view->setTemplateRootPaths($typoScript['view']['templateRootPaths']);
        $view->setLayoutRootPaths($typoScript['view']['layoutRootPaths']);
        $view->setPartialRootPaths($typoScript['view']['partialRootPaths']);
        $view->setTemplatePathAndFilename($template);
        $view->setRequest($this->request);

        // prepare the request
        $reusableRequest = $this->reusableRequestRepository->findByUid($requestUid);

        if (!$reusableRequest) {
            throw new \Exception(sprintf('Reusable request with ID %s was not found', $requestUid));
        }

        $this->apiClient->init($reusableRequest->getClientAlias());

        // important: dispatch event here so that parameter overrides have a higher priority
        $this->eventDispatcher->dispatch(new ModifyRequestFiltersEvent(
            $reusableRequest,
            $cteData['pid']
        ));

        if (!empty($parameterOverrides)) {
            $this->adjustParameters(
                $reusableRequest,
                $parameterOverrides
            );
        }

        // assembling view
        $arguments = [
            'settings' => $this->settings,
            'data' => $cteData,
            'page' => $cteData['pid'],
        ];

        $this->resolveArgumentsContext($arguments, $reusableRequest);
        $view->assignMultiple($arguments);

        $this->addSiteConfigurationToView($view);

        return $view;
    }

    public function resolveArgumentsContext(array &$arguments, ReusableRequest $reusableRequest, bool $isAjaxRequest = false): void
    {
        $queryParameter = $this->request->getQueryParams();

        $response = $this->filterService->processFilterableRequest(
            $reusableRequest,
            $this->request->getAttribute('routing')->getPageId(),
            $this->cacheHelper->getCacheOptionsByReusableRequest($reusableRequest, !(array_key_exists('no_cache', $queryParameter) && (bool)$queryParameter['no_cache'] === true))
        );

        $status = 'ok';

        // preparing possible request error
        if (!$response) {
            $response = ['errors' => $this->apiClient->getLastLogs(true, LogLevel::ERROR)];
            $status = 'error';
        }
        $arguments['api_data'] = $response;
        $arguments['api_meta']['status'] = $status;

        if ($this->hasPagination() && is_array($response)) {
            $this->addPagination($arguments, $response, $reusableRequest, $isAjaxRequest);
        }

        // adding additional information within development context
        if ($this->extensionConfiguration->get(Configuration::EXT_KEY, 'debugMode')) {
            $this->apiClient->init($reusableRequest->getClientAlias(), true);

            // preparing definition
            $schema = $this->apiSchema->getSchema();

            $schema = $this->apiSchema->resolveReferences($schema);
            $endpointData = $schema['paths'][$reusableRequest->getEndpoint()];

            $arguments['api_meta']['schema'] = $endpointData[$reusableRequest->getMethod()]['responses'][200]['schema'];
            $arguments['api_meta']['request'] = $reusableRequest;
        }
    }

    protected function hasPagination(): bool
    {
        $data = $this->configurationManager->getContentObject()->data;

        return $data['tx_ximaapiclient_pagination_active'] || $this->request->hasArgument('paginationParameter');
    }

    protected function addPagination(array &$arguments, array $response, ReusableRequest $reusableRequest, bool $isAjaxRequest = false): void
    {
        if (!isset($response['items'])) {
            return;
        }

        $pageParameter = $this->getPageParameterName();

        // limit to plugin id in order to allow multiple paginated lists on one page
        $currentPage = $this->request->hasArgument($pageParameter) ? (int)$this->request->getArgument($pageParameter) : 1;

        $paginator = new RequestPaginator(
            $response['totalItems'],
            $currentPage,
            (int)($response['itemsPerPage'] ?? 12)
        );

        $pagination = new SlidingWindowPagination(
            $paginator,
            5
        );

        if (!$isAjaxRequest) {
            $allPageNumbers = [];

            foreach ($pagination->getAllPageNumbers() as $page) {
                $allPageNumbers[$page] = [$pageParameter => $page];
            }

            $arguments['pagination'] = [
                'paginator' => $paginator,
                'pagination' => $pagination,
                'paginationLinkArguments' => [
                    'first' => [$pageParameter => 1],
                    'previous' => [$pageParameter => $pagination->getPreviousPageNumber()],
                    'all' => $allPageNumbers,
                    'next' => [$pageParameter => $pagination->getNextPageNumber()],
                    'last' => [$pageParameter => $pagination->getLastPageNumber()],
                ],
            ];
        } else {
            $allPageLinks = [];
            foreach ($pagination->getAllPageNumbers() as $page) {
                $allPageLinks[$page] = $this->generatePaginationLink($page);
            }

            $arguments['pagination'] = [
                'all' => $allPageLinks,
                'first' => $this->generatePaginationLink(1),
                'previous' => $pagination->getPreviousPageNumber() ? [$pagination->getPreviousPageNumber() => $this->generatePaginationLink($pagination->getPreviousPageNumber())] : '',
                'current' => $paginator->getCurrentPageNumber() ? [$paginator->getCurrentPageNumber() => $this->generatePaginationLink($paginator->getCurrentPageNumber())] : '',
                'next' => $pagination->getNextPageNumber() ? [$pagination->getNextPageNumber() => $this->generatePaginationLink($pagination->getNextPageNumber())] : '',
                'last' => $pagination->getLastPageNumber() ? [$pagination->getLastPageNumber() => $this->generatePaginationLink($pagination->getLastPageNumber())] : '',
            ];
        }
    }

    protected function addFilterDataToPagination(array $pagination, array $filterData): array
    {
        foreach (['first', 'previous', 'next', 'last'] as $part) {
            $pagination['paginationLinkArguments'][$part] = $pagination['paginationLinkArguments'][$part] + $filterData;
        }

        foreach ($pagination['paginationLinkArguments']['all'] as $page => $data) {
            $pagination['paginationLinkArguments']['all'][$page] = $pagination['paginationLinkArguments']['all'][$page] + $filterData;
        }

        return $pagination;
    }

    protected function generatePaginationLink(int $page): string
    {
        // workaround to extend current request with page parameter
        $requestParameters = GeneralUtility::_GET();
        $pageParameter = $this->getPageParameterName();
        $requestParameters["tx_ximaapiclient_reusablerequest[$pageParameter]"] = $page;
        return $this->uriBuilder->setArguments($requestParameters)->build();
    }

    protected function getPageParameterName(): string
    {
        $data = $this->configurationManager->getContentObject()->data;

        if ($data['tx_ximaapiclient_pagination_page_parameter']) {
            $pageParameter = $data['tx_ximaapiclient_pagination_page_parameter'];
        } elseif ($this->request->hasArgument('paginationParameter')) {
            $pageParameter = $this->request->getArgument('paginationParameter');
        } else {
            $pageParameter = 'page';
        }

        if (null === ($contentObject = $this->configurationManager->getContentObject())) {
            return 'xac_' . $pageParameter;
        }

        return 'xac_' . $pageParameter . '_p' . $contentObject->data['uid'];
    }

    /**
     * Adjust parameters by configured overrides.
     *
     * @throws GuzzleException
     */
    protected function adjustParameters(ReusableRequest $reusableRequest, array $parameterOverrides): void
    {
        $getParameters = $this->resolveAdditionalGetParameters();

        // parameter overrides
        foreach ($parameterOverrides as $override) {
            $parameter = $override['parameter'];
            $doOverride = false;
            $value = null;

            if (($override['allowGetOverride'] ?? false) && isset($getParameters[$parameter])) {
                // priority 1: GET parameter
                $value = $getParameters[$parameter];
                $doOverride = true;
            } elseif ($override['overrideByStaticValue'] ?? false) {
                // priority 2: static value
                $value = $override['staticOverrideValue'];
                $doOverride = true;
            }

            if (!$doOverride) {
                continue;
            }

            $this->filterService->addFilter(
                $reusableRequest,
                name: $parameter,
                page: $this->request->getAttribute('routing')->getPageId(),
                value: $value
            );
        }
    }

    protected function resolveAdditionalGetParameters(): array
    {
        // prepare get parameters
        $getParameters = [];
        foreach ($this->request->getArguments() as $key => $value) {
            // only replace parameters with prefix "xac_"
            if (!str_starts_with($key, 'xac_')) {
                continue;
            }

            if ($this->hasPagination() && $key === $this->getPageParameterName()) {
                $pageParameterData = explode('_', $key);

                $unprefixedKey = $pageParameterData[1];
            } else {
                $unprefixedKey = (string)str_replace('xac_', '', $key);
            }

            $getParameters[$unprefixedKey] = $value;
        }
        return $getParameters;
    }

    protected function addSiteConfigurationToView(StandaloneView|TemplateView &$view): void
    {
        $siteProcessor = GeneralUtility::makeInstance(SiteProcessor::class);
        $siteProcessorData = $siteProcessor->process(
            $this->configurationManager->getContentObject(),
            [],
            [],
            []
        );
        $view->assign('site', $siteProcessorData['site']);
    }
}
