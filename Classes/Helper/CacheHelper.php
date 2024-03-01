<?php

declare(strict_types=1);

namespace Xima\XimaApiClient\Helper;

use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use Xima\XimaApiClient\Configuration;
use Xima\XimaApiClient\Domain\Model\ReusableRequest;

class CacheHelper
{
    protected QueryBuilder $queryBuilder;
    protected QueryBuilder $tagQueryBuilder;

    public function __construct(
        protected readonly FrontendInterface $cache,
        protected readonly ConnectionPool $connectionPool
    ) {
        $this->queryBuilder = $this->connectionPool->getQueryBuilderForTable('cache_' . Configuration::CACHE_IDENTIFIER);
        $this->tagQueryBuilder = $this->connectionPool->getQueryBuilderForTable('cache_' . Configuration::CACHE_IDENTIFIER . '_tags');
    }

    public function getCacheOptionsByReusableRequest(ReusableRequest $reusableRequest, bool $cachedResponse = true): array
    {
        $options = [];

        // caching?
        if ($reusableRequest->getCacheLifetime() && $reusableRequest->getCacheLifetimePeriod()) {
            $lifetime = strtotime(
                $reusableRequest->getCacheLifetime() . ' ' . $reusableRequest->getCacheLifetimePeriod()
            ) - time();

            $options = [
                'cacheResponse' => $cachedResponse,
                'cacheConfig' => [
                    'lifetime' => $lifetime,
                ],
            ];
        }

        return $options;
    }

    public function getCurrentReusableRequestCacheState(int $reusableRequest): bool|array
    {
        $qb = $this->queryBuilder;

        return $qb
            ->select('*')
            ->from('cache_' . Configuration::CACHE_IDENTIFIER)
            ->where(
                $qb->expr()->like(
                    'identifier',
                    $qb->createNamedParameter('request-' . $reusableRequest . '-%')
                )
            )
            ->orderBy('expires', 'DESC')
            ->setMaxResults(1)
            ->executeQuery()->fetchAssociative();
    }

    public function getApiClientCache(): FrontendInterface
    {
        return $this->cache;
    }

    /**
     * Flush all cached responses of a given reusable request.
     */
    public function flushAllReusableRequests(int $reusableRequest): void
    {
        $this->cache->flushByTag('request-uid-' . $reusableRequest);
    }

    public function flushReusableRequestsByPage(int $page): void
    {
        $this->cache->flushByTag('page-uid-' . $page);
    }

    public function addCacheTags(string $cacheIdentifier, array $cacheTags): void
    {
        $qb = $this->tagQueryBuilder;

        foreach ($cacheTags as $cacheTag) {
            $tag = $qb
                ->resetQueryParts()
                ->select('*')
                ->from('cache_' . Configuration::CACHE_IDENTIFIER . '_tags')
                ->andWhere(
                    $qb->expr()->eq(
                        'identifier',
                        $qb->createNamedParameter($cacheIdentifier)
                    ),
                    $qb->expr()->eq(
                        'tag',
                        $qb->createNamedParameter($cacheTag)
                    ),
                )
                ->setMaxResults(1)
                ->executeQuery()->fetchAssociative();

            if ($tag) {
                continue;
            }

            $qb
                ->insert('cache_' . Configuration::CACHE_IDENTIFIER . '_tags')
                ->values([
                    'identifier' => $cacheIdentifier,
                    'tag' => $cacheTag,
                ])
                ->executeStatement();
        }
    }
}
