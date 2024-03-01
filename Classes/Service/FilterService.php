<?php

declare(strict_types=1);

namespace Xima\XimaApiClient\Service;

use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Xima\XimaApiClient\ApiClient\ApiClient;
use Xima\XimaApiClient\ApiClient\ApiSchema;
use Xima\XimaApiClient\Configuration;
use Xima\XimaApiClient\Domain\Model\ReusableRequest;
use Xima\XimaApiClient\Domain\Repository\ReusableRequestRepository;
use Xima\XimaApiClient\Entity\RequestFilter;
use Xima\XimaApiClient\Helper\CacheHelper;

/**
 * This service handles custom filters for reusable requests
 */
class FilterService
{
    public function __construct(
        protected readonly ApiSchema $apiSchema,
        protected readonly ApiClient $apiClient,
        protected readonly ReusableRequestRepository $reusableRequestRepository,
        protected readonly CacheHelper $cacheHelper
    ) {
    }

    /**
     * Add a new dynamic filter to a reusable request
     *
     * @param string|null $label
     * @param mixed|null $value
     * @param array $plainValues - Adding plain values to the selection
     * @param int|string|null $requestValuesIdentifier - Reusable request UID or for filter values
     * @param callable|null $requestValuesResults - Response object mapping for iterating over result items
     * @param callable|null $requestValuesIdentifierMap - Response object identifier, which is necessary for the specific filter value
     * @param callable|null $requestValuesValueMap - Response object value name, which is necessary for displaying them in the frontend
     * @param bool $requestValuesEmptyDefault - Add an empty entry to the selection
     * @throws GuzzleException
     */
    public function addFilter(ReusableRequest $reusableRequest, string $name, int $page = 0, string $label = null, mixed $default = null, mixed $value = null, bool $multiple = false, array $plainValues = [], int|string $requestValuesIdentifier = null, callable $requestValuesResults = null, callable $requestValuesIdentifierMap = null, callable $requestValuesValueMap = null, bool $requestValuesEmptyDefault = true): void
    {
        // check if parameter is allowed by the schema
        $this->apiClient->init($reusableRequest->getClientAlias());
        $possibleParameters = $this->apiSchema->getParametersByEndpointAndMethod($reusableRequest->getEndpoint(), $reusableRequest->getMethod());
        $accordingParameter = false;

        foreach ($possibleParameters as $possibleParameter) {
            if ($possibleParameter['name'] === $name) {
                $accordingParameter = $possibleParameter;
            }
        }

        if (!$accordingParameter) {
            throw new \Exception(sprintf('Could\'nt find a matching parameter within the reusable request %s called "%s"', $reusableRequest->getUid(), $name));
        }

        // check predefined parameter
        $predefinedRequestParameters = (array)$reusableRequest->getParameters();
        $default = $default ?: $predefinedRequestParameters[$name];

        // create new filter entity
        $filter = new RequestFilter($name, $label, $default, $value, $multiple);
        $filter->setSchema($accordingParameter);

        if (!empty($plainValues)) {
            $filter->setValues($plainValues);
        }

        /**
         * Adding dynamic values to the filter using a predefined reusable request
         */
        if (!is_null($requestValuesIdentifier)) {
            $requestValues = [];

            if ($requestValuesEmptyDefault) {
                $requestValues[] = '';
            }

            if (is_int($requestValuesIdentifier)) {
                $filterValuesReusableRequest = $this->reusableRequestRepository->findByUid($requestValuesIdentifier);
            } elseif (is_string($requestValuesIdentifier)) {
                $filterValuesReusableRequest = $this->reusableRequestRepository->findByPreparedEndpoint($requestValuesIdentifier);
            }

            if (!$filterValuesReusableRequest) {
                throw new \Exception(sprintf('Could\'nt find reusable request %s', $requestValuesIdentifier));
            }

            $options = $this->cacheHelper->getCacheOptionsByReusableRequest($filterValuesReusableRequest);

            $preparedParameters = (array)$reusableRequest->getParameters();

            $cache = $this->cacheHelper->getApiClientCache();

            // get filter values response
            $result = null;

            // caching?
            $hasCache = false;
            $cacheIdentifier = '';
            $cacheResponse = $options['cacheResponse'] ?? false;
            $cacheConfig = $options['cacheConfig'] ?? [
                'lifetime' => Configuration::CACHE_DEFAULT_LIFETIME,
            ];

            // add a cache tag not containing a parameter-based hash in order to be able to remove
            // all cache entries by tag
            $cacheTagRequest = 'request-uid-' . $filterValuesReusableRequest->getUid();
            $cacheTagPage = 'page-uid-' . $page;

            if ($filterValuesReusableRequest->getMethod() === 'get' && $cacheResponse) {
                // add hash for prepared endpoint, i.e. take into account the parameter context for the identifier
                $cacheIdentifier = 'request-' . $filterValuesReusableRequest->getUid() . '-' . sha1($filterValuesReusableRequest->getPreparedEndpoint());

                if (false !== ($response = $cache->get($cacheIdentifier))) {
                    $result = $response;
                    $hasCache = true;

                    // add page tag which may not have been set, yet
                    $this->cacheHelper->addCacheTags($cacheIdentifier, [$cacheTagPage]);
                }
            }

            if (!$hasCache) {
                $this->apiClient->init($reusableRequest->getClientAlias());
                $result = $this->apiClient->request(
                    $filterValuesReusableRequest->getPreparedEndpoint(),
                    $filterValuesReusableRequest->getMethod(),
                    options: $options
                );

                // store response to cache
                if ($filterValuesReusableRequest->getMethod() === 'get' && $cacheResponse) {
                    $cache->set($cacheIdentifier, $result, [$cacheTagRequest, $cacheTagPage], $cacheConfig['lifetime']);
                }
            }

            // map the result object
            $result = $requestValuesResults($result);

            foreach ($result as $item) {
                // map the identifier and value of the result item
                $requestValues[$requestValuesIdentifierMap($item)] = $requestValuesValueMap($item);
            }

            $filter->setValues($requestValues);
        }

        $reusableRequest->addFilter($filter);
    }

    /**
     * Map the controller request arguments to the reusable request filters
     */
    public function mapFilter(ReusableRequest $reusableRequest, array $parameters = []): void
    {
        foreach ($parameters as $key => $value) {
            // workaround for simple arrays
            if (is_array($value) && is_numeric(array_keys($value)[0])) {
                $key .= '[]';
            }
            $filters = [$key => $value];

            // workaround for associative arrays
            if (is_array($value) && is_string(array_keys($value)[0])) {
                foreach ($value as $keyItem => $valueItem) {
                    $filters[$key . "[$keyItem]"] = $valueItem;
                }
            }

            $this->overwriteFilters($filters, $reusableRequest);
        }
    }

    /**
     * Process the reusable request and extending the parameters with the filter values of the reusable request
     *
     *
     * @throws GuzzleException
     */
    public function processFilterableRequest(ReusableRequest $reusableRequest, int $page = 0, array $options = []): array|bool|ResponseInterface
    {
        $cacheResponse = $options['cacheResponse'] ?? false;
        $cacheConfig = $options['cacheConfig'] ?? [
            'lifetime' => Configuration::CACHE_DEFAULT_LIFETIME,
        ];

        $preparedParameters = (array)$reusableRequest->getParameters();

        foreach ($reusableRequest->getFilters() as $filter) {
            if (is_null($filter->getValue())) {
                continue;
            }

            $name = $filter->getName();

            // workaround for arrays
            if (is_array($filter->getValue()) && is_numeric(array_keys($filter->getValue())[0])) {
                $name = str_replace('[]', '', (string)$name);
            }

            $preparedParameters[$name] = $filter->getValue();
        }

        $preparedEndpoint = $this->apiClient->prepareEndpoint($reusableRequest->getEndpoint(), $reusableRequest->getMethod(), $preparedParameters);
        $reusableRequest->setPreparedEndpoint($preparedEndpoint);

        // cache response
        $cache = $this->cacheHelper->getApiClientCache();

        $cacheIdentifier = '';

        // add a cache tag not containing a parameter-based hash in order to be able to remove
        // all cache entries by tag
        $cacheTagRequest = 'request-uid-' . $reusableRequest->getUid();
        $cacheTagPage = 'page-uid-' . $page;

        if ($reusableRequest->getMethod() === 'get' && $cacheResponse) {
            // add hash for prepared endpoint, i.e. take into account the parameter context for the identifier
            $cacheIdentifier = 'request-' . $reusableRequest->getUid() . '-' . sha1($preparedEndpoint);

            if (false !== ($response = $cache->get($cacheIdentifier))) {
                // add page tag which may not have been set, yet
                $this->cacheHelper->addCacheTags($cacheIdentifier, [$cacheTagPage]);

                return $response;
            }
        }

        $this->apiClient->init($reusableRequest->getClientAlias());

        $response = $this->apiClient->request(
            $reusableRequest->getPreparedEndpoint(),
            $reusableRequest->getMethod()
        );

        // store response to cache
        if ($reusableRequest->getMethod() === 'get' && $cacheResponse) {
            $cache->set($cacheIdentifier, $response, [$cacheTagRequest, $cacheTagPage], $cacheConfig['lifetime']);
        }

        return $response;
    }

    /**
     * Overwrite filter values by given array
     */
    private function overwriteFilters(array $filters, ReusableRequest $reusableRequest): void
    {
        foreach ($filters as $key => $value) {
            if (in_array($key, $reusableRequest->getFilterNames())) {
                $requestFilter = $reusableRequest->getFilterByName($key);

                if (!$requestFilter) {
                    return;
                }

                $requestFilter->setValue($value);
            }
        }
    }
}
