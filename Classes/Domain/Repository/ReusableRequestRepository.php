<?php

declare(strict_types=1);

namespace Xima\XimaApiClient\Domain\Repository;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Xima\XimaApiClient\Domain\Model\ReusableRequest;
use Xima\XimaApiClient\Entity\Form\BackendFilter;

/**
 * Class ReusableRequestRepository
 */
class ReusableRequestRepository
{
    final public const TABLE_REUSABLEREQUEST = 'tx_ximaapiclient_reusablerequest';

    /**
     * @var QueryBuilder
     */
    protected QueryBuilder $queryBuilder;

    /**
     * ReusableRequestRepository constructor.
     */
    public function __construct()
    {
        $this->queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_REUSABLEREQUEST);
    }

    /**
     * Find all items
     */
    public function findAll(): ?array
    {
        $result = $this->queryBuilder
            ->resetQueryParts()
            ->select('*')
            ->from(self::TABLE_REUSABLEREQUEST)
            ->orderBy('name')
            ->executeQuery()->fetchAllAssociative();
        return $this->mapResult($result, true);
    }

    /**
     * Find all items by filter
     */
    public function findByDemand(BackendFilter $filter): ?array
    {
        $query = $this->queryBuilder
            ->resetQueryParts()
            ->select('*')
            ->from(self::TABLE_REUSABLEREQUEST);

        if ($search = $filter->getSearch()) {
            $query->orWhere(
                $query->expr()->like('name', $query->createNamedParameter("%$search%")),
                $query->expr()->like('tag', $query->createNamedParameter("%$search%")),
                $query->expr()->like('uid', $query->createNamedParameter("%$search%"))
            );
        }

        if ($filter->getClientAlias()) {
            $query->andWhere($query->expr()->eq('clientAlias', $query->createNamedParameter($filter->getClientAlias())));
        }

        if ($filter->getMethod()) {
            $query->andWhere($query->expr()->eq('method', $query->createNamedParameter($filter->getMethod())));
        }

        $query->orderBy($filter->getSortBy(), $filter->getSortOrder());

        if ($filter->getLimit()) {
            $query->setMaxResults($filter->getLimit());
        }

        $result = $query->executeQuery()->fetchAllAssociative();
        return $this->mapResult($result, true);
    }

    /**
     * Find all reusable requests by their client alias
     */
    public function findByClientAlias(string $clientAlias): array
    {
        $result = $this->queryBuilder
            ->resetQueryParts()
            ->select('*')
            ->from(self::TABLE_REUSABLEREQUEST)
            ->andWhere(
                $this->queryBuilder->expr()->eq('clientAlias', $this->queryBuilder->createNamedParameter($clientAlias))
            )
            ->executeQuery()->fetchAllAssociative();

        return $this->mapResult($result, true);
    }

    /**
     * Find a single reusable request by uid
     */
    public function findByUid(int $uid): ?ReusableRequest
    {
        $result = $this->queryBuilder
            ->resetQueryParts()
            ->select('*')
            ->from(self::TABLE_REUSABLEREQUEST)
            ->andWhere(
                $this->queryBuilder->expr()->eq('uid', $uid)
            )
            ->executeQuery()->fetchAssociative();

        return $this->mapResult($result);
    }

    /**
     * Find a single reusable request by preparedEndpoint
     */
    public function findByPreparedEndpoint(string $preparedEndpoint): ?ReusableRequest
    {
        $result = $this->queryBuilder
            ->resetQueryParts()
            ->select('*')
            ->from(self::TABLE_REUSABLEREQUEST)
            ->andWhere(
                $this->queryBuilder->expr()->eq('preparedEndpoint', $this->queryBuilder->createNamedParameter($preparedEndpoint))
            )
            ->executeQuery()->fetchAssociative();

        return $this->mapResult($result);
    }

    /**
     * Delete a item
     */
    public function delete(int $uid): bool
    {
        return (bool)$this->queryBuilder
            ->resetQueryParts()
            ->delete(self::TABLE_REUSABLEREQUEST)
            ->andWhere(
                $this->queryBuilder->expr()->eq('uid', $uid)
            )
            ->executeStatement();
    }

    /**
     * Add a new ReusableRequest item
     */
    public function add(ReusableRequest $reusableRequest): int
    {
        $result = $this->queryBuilder
            ->resetQueryParts()
            ->insert(self::TABLE_REUSABLEREQUEST)
            ->values([
                'name' => $reusableRequest->getName(),
                'description' => $reusableRequest->getDescription(),
                'clientAlias' => $reusableRequest->getClientAlias(),
                'endpoint' => $reusableRequest->getEndpoint(),
                'operationId' => $reusableRequest->getOperationId(),
                'method' => $reusableRequest->getMethod(),
                'preparedEndpoint' => $reusableRequest->getPreparedEndpoint(),
                'acceptHeader' => $reusableRequest->getAcceptHeader(),
                'tag' => $reusableRequest->getTag(),
                'cacheLifetime' => $reusableRequest->getCacheLifetime(),
                'cacheLifetimePeriod' => $reusableRequest->getCacheLifetimePeriod(),
                'parameters' => $reusableRequest->getParameters(false),
                'registeredTemplates' => json_encode($reusableRequest->getRegisteredTemplates(), JSON_THROW_ON_ERROR),
                'updated' => time(),
                'created' => time(),
            ])
            ->executeStatement();

        if ($result) {
            return (int)$this->queryBuilder->getConnection()->lastInsertId();
        }
        return $result;
    }

    /**
     * Update an existing queue item
     */
    public function update(ReusableRequest $reusableRequest): int
    {
        return (int)$this->queryBuilder
            ->resetQueryParts()
            ->update(self::TABLE_REUSABLEREQUEST)
            ->andWhere(
                $this->queryBuilder->expr()->eq('uid', $reusableRequest->getUid())
            )
            ->set('name', $reusableRequest->getName())
            ->set('description', $reusableRequest->getDescription())
            ->set('clientAlias', $reusableRequest->getClientAlias())
            ->set('endpoint', $reusableRequest->getEndpoint())
            ->set('operationId', $reusableRequest->getOperationId())
            ->set('method', $reusableRequest->getMethod())
            ->set('preparedEndpoint', $reusableRequest->getPreparedEndpoint())
            ->set('acceptHeader', $reusableRequest->getAcceptHeader())
            ->set('tag', $reusableRequest->getTag())
            ->set('cacheLifetime', $reusableRequest->getCacheLifetime())
            ->set('cacheLifetimePeriod', $reusableRequest->getCacheLifetimePeriod())
            ->set('parameters', $reusableRequest->getParameters(false))
            ->set('registeredTemplates', json_encode($reusableRequest->getRegisteredTemplates(), JSON_THROW_ON_ERROR))
            ->set('updated', time())
            ->executeStatement();
    }

    /**
     * Simple data mapper function
     */
    public function dataMapper(string|ReusableRequest $entity, array $item): ReusableRequest
    {
        if (is_string($entity)) {
            $class = $entity;
            $model = new $entity();
        } else {
            $class = $entity::class;
            $model = $entity;
        }

        foreach ($item as $key => $value) {
            $function = 'set' . ucfirst($key);
            if (method_exists($class, $function)) {
                $model->$function($value);
            }
        }

        return $model;
    }

    /**
     * Map the database result to the reusable request object
     */
    private function mapResult(mixed $result, bool $multiple = false): ReusableRequest|array|null
    {
        if (is_null($result) || is_bool($result)) {
            return null;
        }

        if (empty($result)) {
            return $result;
        }

        if ($multiple) {
            $models = [];
            foreach ($result as $item) {
                $models[] = $this->dataMapper(ReusableRequest::class, $item);
            }
            return $models;
        }

        return $this->dataMapper(ReusableRequest::class, $result);
    }
}
