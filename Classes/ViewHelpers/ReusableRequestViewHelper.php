<?php

declare(strict_types=1);

namespace Xima\XimaApiClient\ViewHelpers;

use GuzzleHttp\Exception\GuzzleException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use Xima\XimaApiClient\ApiClient\ApiClient;
use Xima\XimaApiClient\ApiClient\ApiSchema;
use Xima\XimaApiClient\Configuration;
use Xima\XimaApiClient\Domain\Repository\ReusableRequestRepository;
use Xima\XimaApiClient\Event\ModifyRequestFiltersEvent;
use Xima\XimaApiClient\Helper\CacheHelper;
use Xima\XimaApiClient\Service\FilterService;

/**
 * @return string
 */
class ReusableRequestViewHelper extends AbstractViewHelper
{
    public function __construct(
        protected readonly ApiSchema $apiSchema,
        protected readonly ApiClient $apiClient,
        protected readonly ReusableRequestRepository $reusableRequestRepository,
        protected readonly FilterService $filterService,
        protected readonly CacheHelper $cacheHelper,
        protected readonly EventDispatcherInterface $eventDispatcher,
        protected readonly ExtensionConfiguration $extensionConfiguration
    ) {
    }

    /**
     * Initialize arguments
     */
    public function initializeArguments(): void
    {
        $this->registerArgument('uid', 'string', 'Reusable request by ID');
        $this->registerArgument('page', 'string', 'The current page ID', true);
        $this->registerArgument('preparedEndpoint', 'string', 'Reusable request by preparedEndpoint');
        $this->registerArgument('variableName', 'string', 'Variable name for response data', true);
        $this->registerArgument('overrideParameters', 'array', 'Override reusable request parameters');
    }

    /**
     * @throws GuzzleException
     */
    public function render(): void
    {
        $uid = $this->arguments['uid'];
        $preparedEndpoint = $this->arguments['preparedEndpoint'];
        $page = $this->arguments['page'];
        $request = $this->arguments['request'];

        if ($uid) {
            $reusableRequest = $this->reusableRequestRepository->findByUid($uid);
        } elseif ($preparedEndpoint) {
            $reusableRequest = $this->reusableRequestRepository->findByPreparedEndpoint($preparedEndpoint);
        } else {
            throw new \Exception('Reusable request ID or endpoint must be provided');
        }

        if (!$reusableRequest) {
            throw new \Exception(sprintf('Reusable request with ID %s / preparedEndpoint %s was not found', $uid, $preparedEndpoint));
        }

        $this->apiClient->init($reusableRequest->getClientAlias());

        // important: dispatch event here so that parameter overrides have a higher priority
        $this->eventDispatcher->dispatch(new ModifyRequestFiltersEvent(
            $reusableRequest,
            $page
        ));

        if ($this->arguments['overrideParameters'] ?? false) {
            foreach ($this->arguments['overrideParameters'] as $key => $parameter) {
                $this->filterService->addFilter(
                    $reusableRequest,
                    name: $key,
                    page: $page,
                    value: $parameter
                );
            }
        }

        $response = $this->filterService->processFilterableRequest(
            $reusableRequest,
            $page,
            $this->cacheHelper->getCacheOptionsByReusableRequest($reusableRequest)
        );

        $status = 'ok';

        // preparing possible request error
        if (!$response) {
            $response = ['errors' => $this->apiClient->getLastLogs(true, LogLevel::ERROR)];
            $status = 'error';
        }
        $this->arguments['api_data'] = $response;
        $this->arguments['api_meta']['status'] = $status;

        // adding additional information within development context
        if ($this->extensionConfiguration->get(Configuration::EXT_KEY, 'debugMode')) {
            $this->apiClient->init($reusableRequest->getClientAlias());

            // preparing definition
            $schema = $this->apiSchema->getSchema();

            $schema = $this->apiSchema->resolveReferences($schema);
            $endpointData = $schema['paths'][$reusableRequest->getEndpoint()];

            $this->arguments['api_meta']['schema'] = $endpointData[$reusableRequest->getMethod()]['responses'][200]['schema'];
            $this->arguments['api_meta']['request'] = $reusableRequest;
        }

        $this->renderingContext->getVariableProvider()->add($this->arguments['variableName'], $this->arguments);
    }
}
