<?php

declare(strict_types=1);

namespace Xima\XimaApiClient\Controller\Backend;

use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LogLevel;
use TYPO3\CMS\Backend\Attribute\Controller;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Core\DependencyInjection\NotFoundException;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Pagination\ArrayPaginator;
use TYPO3\CMS\Core\Pagination\SlidingWindowPagination;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use Xima\XimaApiClient\Configuration;
use Xima\XimaApiClient\Domain\Model\ReusableRequest;
use Xima\XimaApiClient\Entity\Form\BackendFilter;
use Xima\XimaApiClient\Form\Element\RegisteredTemplateElement;

#[Controller]
final class RequestController extends AbstractController
{
    /**
     * @param int|null $request
     * @param string|null $clientAlias
     * @param string|null $endpoint
     * @param string|null $method
     */
    public function editRequestAction(int $request = null, string $clientAlias = null, string $endpoint = null, string $method = null): ResponseInterface
    {
        $reusableRequest = null;

        if (!$request && !$clientAlias && !$endpoint && !$method) {
            throw new NotFoundException('Missing arguments');
        }

        if ($request) {
            $reusableRequest = $this->reusableRequestRepository->findByUid($request);
            if (!$reusableRequest) {
                throw new NotFoundException('Couldn\'t find the requested reusable request: ' . $request);
            }

            $clientAlias = $reusableRequest->getClientAlias();
            $endpoint = $reusableRequest->getEndpoint();
            $method = $reusableRequest->getMethod();
        }

        $this->apiClient->init($clientAlias);

        $schema = $this->apiSchema->getSchema();

        $this->view->assignMultiple([
            'schema' => $schema,
            'schemaLocation' => $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][Configuration::EXT_KEY]['clients'][$clientAlias]['schemaUrl'],
            'routeData' => $schema['paths'][$endpoint][$method],
            'method' => $method,
            'endpoint' => $endpoint,
            'clientAlias' => $clientAlias,
            'reusableRequest' => $reusableRequest,
            'registeredTemplates' => RegisteredTemplateElement::getRegisteredTemplates(),
        ]);
        $this->generateButtonBar();

        $this->moduleTemplate->setContent($this->view->render());
        return $this->htmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * @param BackendFilter|null $filter
     * @param string|null $sortBy
     * @param string|null $sortOrder
     * @throws \Doctrine\DBAL\Exception
     * @throws RouteNotFoundException
     */
    public function listReusableRequestAction(BackendFilter $filter = null, string $sortBy = null, string $sortOrder = null, bool $reset = false): ResponseInterface
    {
        if ($filter === null || $reset) {
            $filter = new BackendFilter();
        }

        if ($this->request->hasArgument('search')) {
            $filter->setSearch($this->request->getArgument('search'));
        }
        if ($this->request->hasArgument('method')) {
            $filter->setMethod($this->request->getArgument('method'));
        }
        if ($this->request->hasArgument('clientAlias')) {
            $filter->setClientAlias($this->request->getArgument('clientAlias'));
        }

        if ($sortBy && $sortOrder) {
            $filter->setSortBy($sortBy);
            $filter->setSortOrder($sortOrder);
        }

        $result = $this->reusableRequestRepository->findByDemand($filter);

        // get current cache states
        foreach ($result as $request) {
            if (false === ($currentCacheState = $this->cacheHelper->getCurrentReusableRequestCacheState($request->getUid()))) {
                $request->setCurrentCacheState([
                    'cached' => false,
                ]);

                continue;
            }

            $currentCacheState['cached'] = $currentCacheState['expires'] > time();

            $request->setCurrentCacheState($currentCacheState);
        }

        $currentPage = $this->request->hasArgument('currentPage')
            ? (int)$this->request->getArgument('currentPage')
            : 1;

        $paginator = new ArrayPaginator((array)$result, $currentPage, 20);
        $pagination = new SlidingWindowPagination($paginator, 10);

        $this->view->assignMultiple([
            'paginator' => $paginator,
            'pagination' => $pagination,
            'pages' => range(1, $pagination->getLastPageNumber()),
            'filter' => $filter,
            'filterOptions' => [
                'clientConfigs' => array_keys($this->apiClient->getAvailableClientConfigs()),
                'methods' => ['GET', 'PUT', 'POST', 'DELETE', 'OPTIONS', 'HEAD', 'PATCH'],
            ],
        ]);

        $this->generateButtonBar('web_api_client.Backend\Request_listReusableRequest', 'API Client - Reusable Request');

        $this->moduleTemplate->setContent($this->view->render());
        return $this->htmlResponse($this->moduleTemplate->renderContent());
    }

    /*
     * AJAX
     */

    /**
     * @throws GuzzleException
     */
    public function tryTemporaryRequestAction(): ResponseInterface
    {
        $apiArguments = $this->filterRequestArguments(match: '@');
        $apiRequestArguments = $this->filterRequestArguments(filter: 'request#_', requestParameterValueCombination: true);

        $this->apiClient->init($apiArguments['@clientAlias']);

        $endpoint = $this->apiClient->prepareEndpoint($apiArguments['@endpoint'], $apiArguments['@method'], $apiRequestArguments);
        $this->apiClient->init($apiArguments['@clientAlias'], true, [
            'headers' => [
                'Accept' => $apiArguments['@acceptHeader'],
            ],
        ]);

        $response = $this->apiClient->request($endpoint, $apiArguments['@method'], [], ['jsonDecode' => str_contains($apiArguments['@acceptHeader'], 'json'), 'rawResult' => str_contains($apiArguments['@acceptHeader'], 'image')]);
        $statusCode = 200;

        $data = [
            'preparedEndpoint' => $endpoint,
            'statusCode' => $statusCode,
            'arguments' => json_encode($apiRequestArguments, JSON_THROW_ON_ERROR),
            'acceptHeader' => $apiArguments['@acceptHeader'],
        ];

        if (!$response) {
            $unformattedLogs = $this->apiClient->getLastLogs(false, LogLevel::ERROR);
            $logs = $this->apiClient->getLastLogs(true, LogLevel::ERROR);

            // get statuscode of last log entry
            $statusCode = $unformattedLogs[count($unformattedLogs) - 1]['context']['status'];

            $data['responseRaw'] = $unformattedLogs;
            $data['responseRawFormatted'] = $data['response'] = DebuggerUtility::var_dump(
                ['errors' => $logs],
                title: "Statuscode: $statusCode",
                maxDepth: 16,
                return: true
            );
        } elseif (str_contains($apiArguments['@acceptHeader'], 'image')) {
            // generate image preview
            $image = 'data:' . $response->getHeaders()['content-type'][0] . ';base64,' . base64_encode($response->getBody()->getContents());
            $data['response'] = "<div class='image-preview'><img src=$image alt='preview'></div>";
        } else {
            $data['responseRaw'] = $response;

            if (isset($response['_raw'])) {
                $data['responseRawFormatted'] = DebuggerUtility::var_dump($response['_raw'], title: "Statuscode: $statusCode", maxDepth: 16, return: true);
                unset($response['_raw']);
            } else {
                $data['responseRawFormatted'] = DebuggerUtility::var_dump('Activate debugMode in extension config to see the raw response.', title: "Statuscode: $statusCode", maxDepth: 16, return: true);
            }

            $data['response'] = DebuggerUtility::var_dump($response, title: "Statuscode: $statusCode", maxDepth: 16, return: true);
        }

        return new JsonResponse($data);
    }

    /**
     * @throws NotFoundException
     */
    public function editReusableRequestAction(): ResponseInterface
    {
        $arguments = $this->filterRequestArguments();
        $result = $this->createOrUpdateReusableRequest($arguments);

        return new JsonResponse(is_bool($result) ? [] : $result->toArray());
    }

    /**
     * Delete reusable request
     *
     * @param int|null $uid
     * @throws \Exception
     */
    public function deleteReusableRequestAction(int $uid = null): ResponseInterface
    {
        $arguments = $this->filterRequestArguments();
        $result = $this->reusableRequestRepository->delete($uid ?: (int)$arguments['uid']);

        $response = [];

        if ($result) {
            $response['message'] = 'Successfully deleted the element';
        } else {
            throw new \Exception('Delete error');
        }

        return new JsonResponse($response);
    }

    /*
     * HELPER
     */
    /**
     * Helper function for preparing request arguments
     *
     * @return array<string>
     */
    private function filterRequestArguments(string $filter = 'request#', string $match = null, bool $requestParameterValueCombination = false): array
    {
        $arguments = $this->request->getArguments();
        $requestArguments = [];
        foreach ($arguments as $key => $argument) {
            if (str_starts_with($key, $filter)) {
                $key = str_replace($filter, '', $key);
                if ($match && !str_starts_with($key, $match)) {
                    continue;
                }
                $requestArguments[$key] = $argument;
            }
        }

        /*
         * Combine request parameter with request values (which were send separately)
         * Example:
         *  request#_1#parameter = foo
         *  request#_1#value = bar
         *
         * This was necessary to avoid POST parameter name issues eg. wineRecommendations.varietal or wineRecommendations[]
         */
        if ($requestParameterValueCombination) {
            $requestParameterValueCombinationResult = [];
            $arr = [];
            foreach ($requestArguments as $key => $argument) {
                $tmp = explode('#', $key);
                $arr[$tmp[0]][$tmp[1]] = $argument;
            }
            foreach ($arr as $argument) {
                if (!array_key_exists('value', $argument)) {
                    continue;
                }
                if (is_array($argument['parameter'])) {
                    foreach ($argument['parameter'] as $parameter) {
                        $requestParameterValueCombinationResult[str_replace('[]', '', (string)$parameter)] = $argument['value'];
                    }
                } else {
                    $requestParameterValueCombinationResult[$argument['parameter']] = $argument['value'];
                }
            }

            $requestArguments = $requestParameterValueCombinationResult;
        }
        return $requestArguments;
    }

    /**
     * Create a new reusable request or update an existing reusable request
     *
     * @param array<string,string> $arguments
     * @throws NotFoundException
     */
    private function createOrUpdateReusableRequest(array $arguments): ReusableRequest|bool
    {
        $newEntity = false;

        // distinguish between new entity or existing entity
        if (!array_key_exists('uid', $arguments) || $arguments['uid'] === '') {
            $newEntity = true;
            unset($arguments['uid']);
            $reusableRequest = new ReusableRequest();
        } else {
            $reusableRequest = $this->reusableRequestRepository->findByUid((int)$arguments['uid']);
            if (!$reusableRequest) {
                throw new NotFoundException(sprintf('Reusable Request entity with uid %u not found', (int)$arguments['uid']));
            }
        }

        // mapping request arguments to domain object
        $reusableRequest = $this->reusableRequestRepository->dataMapper($reusableRequest, $arguments);

        // add or update entity to repository
        if ($newEntity) {
            $uid = $this->reusableRequestRepository->add($reusableRequest);
            if ($uid) {
                $reusableRequest->setUid($uid);
            } else {
                return false;
            }
        } else {
            $result = $this->reusableRequestRepository->update($reusableRequest);
            if (!$result) {
                return false;
            }
        }
        return $reusableRequest;
    }

    public function flushReusableRequestCacheAction(int $uid): ResponseInterface
    {
        $this->cacheHelper->flushAllReusableRequests($uid);

        return new JsonResponse([
            'state' => 'success',
            'message' => 'Cache flushed successfully',
        ]);
    }

    public function warmupReusableRequestCacheAction(int $uid): ResponseInterface
    {
        if (!($request = $this->reusableRequestRepository->findByUid($uid))) {
            return new JsonResponse([
                'state' => 'error',
                'message' => 'Request not found',
            ]);
        }

        // flush in advance in order to get a fresh new cache entry
        $this->cacheHelper->flushAllReusableRequests($uid);

        $this->apiClient->init($request->getClientAlias());

        $this->filterService->processFilterableRequest(
            $request,
            0,
            $this->cacheHelper->getCacheOptionsByReusableRequest($request)
        );

        $result = $this->cacheHelper->getCurrentReusableRequestCacheState($uid);

        if ($result === false) {
            return new JsonResponse([
                'state' => 'error',
                'message' => 'Error while warming up the cache',
            ]);
        }

        return new JsonResponse([
            'state' => 'success',
            'message' => 'Cache warmed up successfully',
            'data' => $result,
        ]);
    }

    public function clearPageAndRequestCacheAction(ServerRequestInterface $request): ResponseInterface
    {
        $page = $request->getQueryParams()['id'] ?? null;

        // clear page cache as normal
        $response = $this->clearPageCacheController->mainAction($request);

        $jsonResponse = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        if (!($jsonResponse['success'] ?? false) || !$page) {
            return $response;
        }

        // clear the requests by page id
        $this->cacheHelper->flushReusableRequestsByPage((int)$page);

        return new JsonResponse([
            'success' => true,
            'title' => $GLOBALS['LANG']->sL('LLL:EXT:backend/Resources/Private/Language/locallang.xlf:clearcache.title'),
            'message' => 'Successfully cleared page cache (including API Client requests)',
        ]);
    }
}
