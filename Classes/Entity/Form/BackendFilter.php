<?php

declare(strict_types=1);

namespace Xima\XimaApiClient\Entity\Form;

use TYPO3\CMS\Extbase\Persistence\QueryInterface;

/**
 * Class BackendFilter
 */
class BackendFilter
{
    final public const SORT_NAME = 'name';
    final public const SORT_UID = 'uid';
    final public const SORT_TAG = 'tag';
    final public const SORT_METHOD = 'method';
    final public const ORDER_ASC = QueryInterface::ORDER_ASCENDING;
    final public const ORDER_DESC = QueryInterface::ORDER_DESCENDING;

    protected string $search = '';
    protected string $clientAlias = '';
    protected string $method = '';
    protected ?int $limit = null;

    public function __construct(protected string $sortBy = self::SORT_NAME, protected string $sortOrder = self::ORDER_ASC)
    {
    }

    public function getSearch(): string
    {
        return $this->search;
    }

    public function setSearch(string $search): void
    {
        $this->search = $search;
    }

    public function getClientAlias(): string
    {
        return $this->clientAlias;
    }

    public function setClientAlias(string $clientAlias): void
    {
        $this->clientAlias = $clientAlias;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function setMethod(string $method): void
    {
        $this->method = $method;
    }

    public function getSortBy(): string
    {
        return $this->sortBy;
    }

    public function setSortBy(string $sortBy): void
    {
        $this->sortBy = $sortBy;
    }

    public function getSortOrder(): string
    {
        return $this->sortOrder;
    }

    public function setSortOrder(string $sortOrder): void
    {
        $this->sortOrder = $sortOrder;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function setLimit(?int $limit): void
    {
        $this->limit = $limit;
    }
}
