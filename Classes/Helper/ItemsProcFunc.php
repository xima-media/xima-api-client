<?php

declare(strict_types=1);

namespace Xima\XimaApiClient\Helper;

use TYPO3\CMS\Core\Database\ConnectionPool;
use Xima\XimaApiClient\ApiClient\ApiClient;
use Xima\XimaApiClient\ApiClient\ApiSchema;
use Xima\XimaApiClient\Domain\Repository\ReusableRequestRepository;

class ItemsProcFunc
{
    public function __construct(
        protected readonly ReusableRequestRepository $reusableRequestRepository,
        protected readonly ConnectionPool $connectionPool,
        protected readonly ApiClient $apiClient,
        protected readonly ApiSchema $apiSchema
    ) {
    }

    /**
     * Get all parameters available in the request
     */
    public function getAvailableRequestParameters(array &$config): void
    {
        if ($config['table'] === 'tx_ximaapiclient_parameter_override') {
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_content');

            $ttContent = $queryBuilder->select('*')
                ->from('tt_content')
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter($config['row']['foreign_uid'])
                    )
                )
                ->setMaxResults(1)
                ->executeQuery()->fetchAssociative();

            $requestUid = $ttContent['tx_ximaapiclient_request'] ?? 0;
        } else {
            $requestUid = $config['row']['tx_ximaapiclient_request'] ?? 0;
        }

        $reusableRequest = $this->reusableRequestRepository->findByUid((int)$requestUid);

        if ($reusableRequest === null) {
            return;
        }

        $this->apiClient->init($reusableRequest->getClientAlias());

        $parameters = $this->apiSchema->getParametersByEndpointAndMethod(
            $reusableRequest->getEndpoint(),
            'get'
        );

        foreach ($parameters as $parameter) {
            $config['items'][] = [$parameter['name'], $parameter['name']];
        }
    }
}
