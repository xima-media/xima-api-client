<?php

declare(strict_types=1);

namespace Xima\XimaApiClient\Helper;

use Doctrine\DBAL\Exception;
use TYPO3\CMS\Core\Database\ConnectionPool;

class RequestHelper
{
    public function __construct(
        protected readonly ConnectionPool $connectionPool
    ) {
    }

    /**
     * Returns the parameter override configuration
     *
     * @param int $plugin The uid of the plugin
     * @throws Exception
     */
    public function getParameterOverrides(int $plugin): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_ximaapiclient_parameter_override');

        $ttContent = $queryBuilder->select('*')
            ->from('tx_ximaapiclient_parameter_override')
            ->where(
                $queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq('foreign_uid', $queryBuilder->createNamedParameter($plugin)),
                    $queryBuilder->expr()->eq('foreign_table', $queryBuilder->createNamedParameter('tt_content'))
                )
            )
            ->executeQuery()->fetchAllAssociative();

        // prepare result
        $result = [];

        foreach ($ttContent ?: [] as $row) {
            $result[$row['parameter']] = $row;
        }

        return $result;
    }
}
