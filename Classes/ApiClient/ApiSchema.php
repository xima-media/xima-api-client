<?php

declare(strict_types=1);

namespace Xima\XimaApiClient\ApiClient;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\InvalidArgumentException;
use GuzzleHttp\Utils;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Log\Channel;
use Xima\XimaApiClient\Configuration;
use Xima\XimaApiClient\Event\ModifySchemaEvent;
use Xima\XimaApiClient\Helper\CacheHelper;

class ApiSchema
{
    protected array $schema;

    public function __construct(
        protected readonly CacheHelper $cacheHelper,
        #[Channel('xima_api_client')]
        protected LoggerInterface $logger,
        protected readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * Retrieve the api schema and cache it
     *
     *
     * @throws GuzzleException
     */
    public function retrieveSchema(Client $client, string $url, string $clientAlias, array $options = []): mixed
    {
        $clientOptions = $options['clientOptions'] ?? [];
        $clientOptions['headers']['Accept'] = 'application/json';
        $urlHash = sha1($url);
        $cache = $this->cacheHelper->getApiClientCache();

        if (false !== ($schema = $cache->get('api_schema-' . $urlHash))) {
            $this->schema = is_array($schema) ? $schema : [];

            return $schema;
        }

        try {
            $response = $client->request('GET', $url, $clientOptions);
        } catch (\Exception $e) {
            if (method_exists($e, 'getResponse') && $e->getResponse()) {
                $this->logger->error('Failed to retrieve the api schema from {url} (status {status}): {message}', [
                    'url' => $url,
                    'status' => $e->getResponse()->getStatusCode(),
                    'message' => $e->getResponse()->getBody()->getContents(),
                ]);
            } else {
                $this->logger->error('Failed to retrieve the api schema from {url} without response', [
                    'url' => $url,
                    'exception' => $e->getMessage(),
                ]);
            }

            return false;
        }

        try {
            $schema = Utils::jsonDecode($response->getBody()->getContents(), true);
        } catch (InvalidArgumentException $e) {
            $this->logger->error('API schema from {url} couldn\'t be json-decoded: {message}', [
                'url' => $url,
                'message' => $e->getMessage(),
            ]);

            return false;
        }

        // modify the schema
        $event = $this->eventDispatcher->dispatch(new ModifySchemaEvent((array)$schema));

        $schema = $event->getSchema();

        $cache->set('api_schema-' . $urlHash, $schema, [
            'schema-' . $clientAlias,
        ], Configuration::CACHE_DEFAULT_LIFETIME);

        $this->schema = $schema;

        return $schema;
    }

    public function getApiEndpointByOperationId(string $operationId): string|null
    {
        if (!($schema = $this->getSchema()) || !isset($schema['paths'])) {
            return null;
        }

        foreach ($schema['paths'] as $endpoint => $pathData) {
            foreach ($pathData as $operationData) {
                if ($operationId === ($operationData['operationId'] ?? '')) {
                    return $endpoint;
                }
            }
        }

        return null;
    }

    /**
     * @param string $endpoint The *unprepared* endpoint uri (i.e. parameters aren't replaced, yet)
     */
    public function getParametersByEndpointAndMethod(string $endpoint, string $method): array
    {
        // forgiving
        $method = strtolower($method);

        if (!($schema = $this->getSchema())) {
            return [];
        }

        return $schema['paths'][$endpoint][$method]['parameters'] ?? [];
    }

    /**
     * Helper function to resolve all links "#/definitions/" for model definitions
     * ToDo: move this to another location, maybe some kind of helper
     */
    public function resolveReferences(array $array, int $depth = 0): array
    {
        // avoid infinite loops caused by circles in schema
        if ($depth > 5) {
            return $array;
        }

        foreach ($array as $key => $item) {
            if (is_array($item)) {
                $array[$key] = $this->resolveReferences($item, ++$depth);
            }

            if ($key === '$ref') {
                $definition = str_replace('#/definitions/', '', (string)$item);
                return $this->resolveReferences($this->schema['definitions'][$definition] ?? [], ++$depth);
            }
        }

        return $array;
    }

    public function setSchema(array $schema): void
    {
        $this->schema = $schema;
    }

    /**
     * Run retrieveSchema() before calling getSchema()!
     */
    public function getSchema(): array
    {
        return $this->schema;
    }
}
