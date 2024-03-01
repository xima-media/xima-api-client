<?php

declare(strict_types=1);

namespace Xima\XimaApiClient\ApiClient;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\InvalidArgumentException;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Utils;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\DependencyInjection\NotFoundException;
use Xima\XimaApiClient\Configuration;
use Xima\XimaApiClient\Event\ModifyResponseEvent;
use Xima\XimaApiClient\Helper\LogHelper;

class ApiClient
{
    final public const PARAMETER_TYPE_MAPPINGS = [
        'string' => 'string',
        'array' => 'array',
        'boolean' => 'bool',
    ];

    protected ?Client $client = null;
    protected array $clientConfig;

    public function __construct(
        protected readonly LogHelper $logHelper,
        protected readonly ApiSchema $apiSchema,
        protected readonly EventDispatcherInterface $eventDispatcher,
        protected readonly ExtensionConfiguration $extensionConfiguration
    ) {
    }

    /**
     * Initialize the http client as defined in the client identified by its alias and retrieve the schema
     *
     * @param array $overrideClientConfig Override certain config set in the global client config defined in TYPO3_CONF_VARS
     * @throws GuzzleException
     */
    public function init(string $clientAlias, bool $forceReinitialize = false, array $overrideClientConfig = []): void
    {
        $this->logHelper->clearLogs();

        if ($this->client && !$forceReinitialize) {
            return;
        }

        $clientConfig = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][Configuration::EXT_KEY]['clients'][$clientAlias] ?? [];

        if (!empty($overrideClientConfig)) {
            $clientConfig = array_replace_recursive($clientConfig, $overrideClientConfig);
        }

        if (!$this->checkClientConfig($clientConfig)) {
            return;
        }

        $this->setClientConfig($clientConfig);

        $headers = [
            'Accept' => 'application/hal+json',
        ];

        if (($this->clientConfig['headers'] ?? false) && is_array($this->clientConfig['headers'])) {
            $headers = array_replace_recursive($headers, $this->clientConfig['headers']);
        }

        // add guzzle handler for debugging the requests
        $stack = new HandlerStack();
        $stack->setHandler(new CurlHandler());

        $middleware = Middleware::tap(function (RequestInterface $request) {
            if ($this->extensionConfiguration->get(Configuration::EXT_KEY, 'debugMode')) {
                $body = (string)$request->getBody();

                if (!empty($body)) {
                    $body = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
                }

                $this->logHelper->logDebug('API Client Request', [
                    'url' => $request->getUri(),
                    'body' => $body,
                    'headers' => $request->getHeaders(),
                ]);
            }
        });
        $stack->push($middleware);

        $this->client = new Client([
            RequestOptions::HEADERS => $headers,
            RequestOptions::TIMEOUT => 30,
            'handler' => $stack,
        ]);

        $this->apiSchema->retrieveSchema($this->client, $this->clientConfig['schemaUrl'], $clientAlias, [
            'clientConfig' => $clientConfig,
        ]);
    }

    /**
     * @return array|bool|ResponseInterface Response (as array or ResponseInterface) else false if an error occurs
     *
     * @throws GuzzleException
     */
    public function request(string $endpoint, string $method, array $data = [], array $options = []): array|bool|ResponseInterface
    {
        $jsonDecode = $options['jsonDecode'] ?? true;
        $clientOptions = $options['clientOptions'] ?? [];
        $rawResult = $options['rawResult'] ?? false;

        $this->logHelper->clearLogs();

        if (!$this->client) {
            $this->logHelper->logError('The ApiClient request to {endpoint} failed because the client has not being initialized correctly, yet.', [
                'endpoint' => $endpoint,
            ]);

            return false;
        }

        try {
            $response = $this->client->request($method, rtrim((string)$this->clientConfig['host'], '/') . $endpoint, $clientOptions);
        } catch (\Exception $e) {
            if (method_exists($e, 'getResponse') && $e->getResponse()) {
                $errorData = [
                    'endpoint' => $endpoint,
                    'status' => $e->getResponse()->getStatusCode(),
                ];

                if ($this->extensionConfiguration->get(Configuration::EXT_KEY, 'debugMode')) {
                    $error = 'The ApiClient request to {endpoint} failed (status {status}): {message}';
                    $errorData['message'] = $e->getResponse()->getBody()->getContents();
                } else {
                    $error = 'The ApiClient request to {endpoint} failed (status {status}).';
                }

                $this->logHelper->logError($error, $errorData);
            } else {
                $this->logHelper->logError('The ApiClient request to {endpoint} failed without response: {exception}', [
                    'endpoint' => $endpoint,
                    'exception' => $e->getMessage(),
                ]);
            }

            return false;
        }

        if ($this->extensionConfiguration->get(Configuration::EXT_KEY, 'debugMode')) {
            $this->logHelper->logDebug('API Client Response', [
                'statuscode' => $response->getStatusCode(),
                'reasonphrase' => $response->getReasonPhrase(),
                'bodymetadata' => $response->getBody()->getMetadata(),
                'bodysize' => $response->getBody()->getSize(),
                'headers' => $response->getHeaders(),
            ]);
        }

        if ($response->getStatusCode() !== 200) {
            $errorData = [
                'endpoint' => $endpoint,
                'status' => $response->getStatusCode(),
            ];

            if ($this->extensionConfiguration->get(Configuration::EXT_KEY, 'debugMode')) {
                $error = 'The ApiClient request to {endpoint} failed (status {status}): {message}';
                $errorData['message'] = $response->getBody()->getContents();
            } else {
                $error = 'The ApiClient request to {endpoint} failed (status {status}).';
            }

            $this->logHelper->logError($error, $errorData);

            return false;
        }

        if ($jsonDecode) {
            try {
                $response = Utils::jsonDecode($response->getBody()->getContents(), true);

                if ($this->extensionConfiguration->get(Configuration::EXT_KEY, 'debugMode')) {
                    $response['_raw'] = $response;
                }
            } catch (InvalidArgumentException $e) {
                $this->logHelper->logError('The ApiClient Response from {endpoint} couldn\'t be json-decoded: {message}', [
                    'endpoint' => $endpoint,
                    'message' => $e->getMessage(),
                ]);

                return false;
            }
        }

        if ($rawResult) {
            return $response;
        }

        // modify the response
        $event = $this->eventDispatcher->dispatch(new ModifyResponseEvent($response));

        return $event->getResponse();
    }

    /**
     * @param array $options General api client options
     *
     * @return mixed Response or false if an error occurs
     * @throws GuzzleException
     */
    public function get(string $endpoint, array $options = []): mixed
    {
        return $this->request($endpoint, 'GET', [], $options);
    }

    /**
     * @param array $data Http data
     * @param array $options General api client options
     *
     * @return mixed Response or false if an error occurs
     * @throws GuzzleException
     */
    public function post(string $endpoint, array $data = [], array $options = []): mixed
    {
        return $this->request($endpoint, 'POST', $data, $options);
    }

    public function checkClientConfig(array $clientConfig): bool
    {
        // check domain format including health check
        $host = $clientConfig['host'] ?? false;

        if (!$host) {
            $this->logHelper->logError('The ApiClient config is invalid: host not set in the client configuration.');

            return false;
        }

        // check schema url (health check is done in ApiSchema::retrieveSchema())
        $schemaUrl = $clientConfig['schemaUrl'] ?? false;

        if (!$schemaUrl) {
            $this->logHelper->logError('The ApiClient config is invalid: schemaUrl not set in the client configuration.');

            return false;
        }

        return true;
    }

    public function getClientConfig(): array
    {
        return $this->clientConfig;
    }

    public function setClientConfig(array $clientConfig): void
    {
        $clientConfig['host'] = rtrim((string)$clientConfig['host'], '/');

        $this->clientConfig = $clientConfig;
    }

    public function getClientHost(): ?string
    {
        $clientConfig = $this->getClientConfig();

        if (!$clientConfig || !isset($clientConfig['host'])) {
            return null;
        }

        $urlParts = parse_url((string)$clientConfig['host']);

        return $urlParts['scheme'] . '://' . $urlParts['host'];
    }

    /**
     * Magic method for providing convenience methods
     * Example: getEventCollection(), getEventItem(1), ...
     *
     *
     * @return mixed
     *
     * @throws GuzzleException
     */
    public function __call(string $name, array $arguments): mixed
    {
        $locale = (string)$arguments[0];

        // get endpoint out of method name
        $endpoint = $this->apiSchema->getApiEndpointByOperationId($name);

        $endpoint = str_replace('{_locale}', $locale, (string)$endpoint);

        if (isset($arguments[1])) {
            $endpoint = str_replace('{id}', (string)$arguments[1], $endpoint);
        }

        return $this->get($endpoint);
    }

    /**
     * Replaces parameters of type "path" in the $endpoint uri and appends those of type "query" as a query string.
     * Only parameters existing in the schema are allowed. Parameter types are enforced in order to avoid sql injection.
     */
    public function prepareEndpoint(string $endpoint, string $method, array $parameters): string
    {
        $queryParameters = [];

        $parameterData = $this->apiSchema->getParametersByEndpointAndMethod($endpoint, $method);

        foreach ($parameterData as $parameter) {
            if (!($parameter['name'] ?? false)) {
                continue;
            }

            $value = $parameters[$parameter['name']];

            // ensure type
            if (isset(static::PARAMETER_TYPE_MAPPINGS[$parameter['type']])) {
                settype($value, static::PARAMETER_TYPE_MAPPINGS[$parameter['type']]);
            }

            if ($parameter['in'] === 'path') {
                $endpoint = str_replace('{' . $parameter['name'] . '}', (string)$parameters[$parameter['name']], $endpoint);
            } elseif ($parameter['in'] === 'query') {
                // avoid empty query parameters
                if (!isset($parameters[$parameter['name']]) || !$parameters[$parameter['name']]) {
                    continue;
                }

                if (is_array($parameters[$parameter['name']])) {
                    // remove empty values
                    $parameters[$parameter['name']] = array_filter($parameters[$parameter['name']]);

                    if (empty($parameters[$parameter['name']])) {
                        continue;
                    }
                }

                $queryParameters[$parameter['name']] = $parameters[$parameter['name']];
            }
        }

        if (!empty($queryParameters)) {
            $endpoint .= '?' . http_build_query($queryParameters);
        }

        return $endpoint;
    }

    public function getLastLogs(bool $format = false, string $minLogLevel = LogLevel::DEBUG): array
    {
        return $this->logHelper->getLastLogs($format, $minLogLevel);
    }

    /**
     * Get all available api client configs
     */
    public function getAvailableClientConfigs(): array
    {
        return $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][Configuration::EXT_KEY]['clients'] ?? [];
    }

    /**
     * Get a specific client config entry
     *
     * @throws NotFoundException
     */
    public function getClientConfigByAlias(string $clientAlias): array
    {
        $clientConfigs = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][Configuration::EXT_KEY]['clients'];

        if (!array_key_exists($clientAlias, $clientConfigs)) {
            throw new NotFoundException(sprintf('Could not found api client config %s within api configuration %s', $clientAlias, "\$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][" . Configuration::EXT_KEY . "]['clients']"));
        }

        return $clientConfigs[$clientAlias];
    }
}
