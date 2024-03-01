<?php

declare(strict_types=1);

namespace Xima\XimaApiClient\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use Xima\XimaApiClient\Entity\RequestFilter;

/**
 * Class ReusableRequest
 */
class ReusableRequest extends AbstractEntity
{
    protected string $name = '';
    protected string $description = '';
    protected string $clientAlias = '';
    protected string $endpoint = '';
    protected string $operationId = '';
    protected string $tag = '';
    protected string $method = '';
    protected string $preparedEndpoint = '';
    protected string $acceptHeader = '';
    protected string $parameters = '';
    protected string $cacheLifetime = '';
    protected string $cacheLifetimePeriod = '';
    protected ?ObjectStorage $filters = null;
    protected ?array $registeredTemplates = null;
    protected ?array $currentCacheState = null;

    /**
     * ReusableRequest constructor.
     */
    public function __construct()
    {
        $this->filters = $this->filters ?: new ObjectStorage();
        $this->registeredTemplates = $this->registeredTemplates ?: [];
    }

    /**
     * @return array<string,string>
     */
    public function toArray(): array
    {
        // ToDO: make this more generic
        return [
            'uid' => $this->getUid(),
            'name' => $this->getName(),
            'clientAlias' => $this->getClientAlias(),
            'endpoint' => $this->getEndpoint(),
            'operationId' => $this->getOperationId(),
            'tag' => $this->getTag(),
            'method' => $this->getMethod(),
            'preparedEndpoint' => $this->getPreparedEndpoint(),
            'acceptHeader' => $this->getAcceptHeader(),
            'parameters' => $this->getParameters(),
        ];
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getClientAlias(): string
    {
        return $this->clientAlias;
    }

    public function setClientAlias(string $clientAlias): void
    {
        $this->clientAlias = $clientAlias;
    }

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    public function setEndpoint(string $endpoint): void
    {
        $this->endpoint = $endpoint;
    }

    public function getOperationId(): string
    {
        return $this->operationId;
    }

    public function setOperationId(string $operationId): void
    {
        $this->operationId = $operationId;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function setMethod(string $method): void
    {
        $this->method = $method;
    }

    public function getPreparedEndpoint(): string
    {
        return $this->preparedEndpoint;
    }

    public function setPreparedEndpoint(string $preparedEndpoint): void
    {
        $this->preparedEndpoint = $preparedEndpoint;
    }

    public function getParameters(bool $forceArray = true): array|string|null
    {
        if ($forceArray) {
            return $this->parameters ? json_decode($this->parameters, true, 512, JSON_THROW_ON_ERROR) : [];
        }
        return $this->parameters;
    }

    public function setParameters(string $parameters): void
    {
        $this->parameters = $parameters;
    }

    public function getTag(): string
    {
        return $this->tag;
    }

    public function setTag(string $tag): void
    {
        $this->tag = $tag;
    }

    public function setUid(int|string $uid): void
    {
        $this->uid = (int)$uid;
    }

    public function getAcceptHeader(): string
    {
        return $this->acceptHeader;
    }

    public function setAcceptHeader(string $acceptHeader): void
    {
        $this->acceptHeader = $acceptHeader;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getFilters(): ?ObjectStorage
    {
        return $this->filters;
    }

    public function setFilters(?ObjectStorage $filters): void
    {
        $this->filters = $filters;
    }

    public function addFilter(RequestFilter $filter): void
    {
        $this->filters->attach($filter);
    }

    public function getFilterByName(string $name): ?RequestFilter
    {
        foreach ($this->filters as $filter) {
            if ($filter->getName() === $name) {
                return $filter;
            }
        }
        return null;
    }

    public function getFilterNames(): array
    {
        $names = [];
        foreach ($this->filters as $filter) {
            $names[] = $filter->getName();
        }
        return $names;
    }

    public function getIndexedFilters(): array
    {
        $result = [];

        foreach ($this->filters as $filter) {
            $result[trim(preg_replace('@[^a-z]@i', '_', (string)$filter->getName()), '_')] = $filter;
        }

        return $result;
    }

    public function getCacheLifetime(): string
    {
        return $this->cacheLifetime;
    }

    public function setCacheLifetime(string $cacheLifetime): void
    {
        $this->cacheLifetime = $cacheLifetime;
    }

    public function getCacheLifetimePeriod(): string
    {
        return $this->cacheLifetimePeriod;
    }

    public function setCacheLifetimePeriod(string $cacheLifetimePeriod): void
    {
        $this->cacheLifetimePeriod = $cacheLifetimePeriod;
    }

    public function getRegisteredTemplates(): ?array
    {
        return $this->registeredTemplates;
    }

    public function setRegisteredTemplates(array|null|string $registeredTemplates): void
    {
        $registeredTemplates = $registeredTemplates ?: [];
        if (is_string($registeredTemplates)) {
            $registeredTemplates = json_decode($registeredTemplates, true, 512, JSON_THROW_ON_ERROR);
        }
        $this->registeredTemplates = $registeredTemplates;
    }

    public function getCurrentCacheState(): ?array
    {
        return $this->currentCacheState;
    }

    public function setCurrentCacheState(?array $currentCacheState): void
    {
        $this->currentCacheState = $currentCacheState;
    }
}
