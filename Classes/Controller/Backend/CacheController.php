<?php

declare(strict_types=1);

namespace Xima\XimaApiClient\Controller\Backend;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Attribute\Controller;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Xima\XimaApiClient\Configuration;

#[Controller]
final class CacheController extends AbstractController
{
    public function cacheAction(string $clientAlias = null, string $flush = null, string $warmup = null): ResponseInterface
    {
        $clientConfigs = $this->apiClient->getAvailableClientConfigs();

        if ($clientAlias) {
            $requests = [];

            foreach ($this->reusableRequestRepository->findByClientAlias($clientAlias) as $request) {
                if (!$request->getCacheLifetime() || !$request->getCacheLifetimePeriod()) {
                    continue;
                }

                $requests[] = $request;
            }

            if ($flush) {
                // schema
                $this->cacheHelper->getApiClientCache()->flushByTag(
                    'schema-' . $clientAlias
                );

                foreach ($requests as $request) {
                    $this->cacheHelper->flushAllReusableRequests($request->getUid());
                }

                $this->addFlashMessage(
                    "The cache for the client \"$clientAlias\" has been flushed successfully."
                );
            }

            if ($warmup) {
                $this->apiClient->init($clientAlias);

                foreach ($requests as $request) {
                    // flush in advance in order to get a fresh new cache entry
                    $this->cacheHelper->flushAllReusableRequests($request->getUid());

                    $this->filterService->processFilterableRequest(
                        $request,
                        0,
                        $this->cacheHelper->getCacheOptionsByReusableRequest($request)
                    );
                }

                $this->addFlashMessage(
                    "The cache for the client \"$clientAlias\" has been warmed up successfully."
                );
            }
        }

        $this->view->assignMultiple([
            'clientConfigs' => $clientConfigs,
            'cacheTableStatus' => $this->getCacheTablesStatus(),
        ]);

        $this->generateButtonBar('web_api_client.Backend\Cache_cache', 'API Client - Response cache');

        /* ToDo: deprecated functions? https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ExtensionArchitecture/HowTo/BackendModule/CreateModuleWithExtbase.html#create-a-backend-module-with-extbase */
        $this->moduleTemplate->setContent($this->view->render());

        return $this->htmlResponse($this->moduleTemplate->renderContent());
    }

    protected function getCacheTablesStatus(): array
    {
        $status = [];
        $tables = [
            'cache_' . Configuration::CACHE_IDENTIFIER,
            'cache_' . Configuration::CACHE_IDENTIFIER . '_tags',
        ];
        $databaseName = $this->connectionPool->getConnectionByName($this->connectionPool->getConnectionNames()[0])->getDatabase();

        foreach ($tables as $table) {
            $qb = $this->connectionPool->getQueryBuilderForTable($table);

            $result = $qb
                ->select('table_name', 'data_length', 'index_length', 'table_rows')
                ->from('information_schema.TABLES')
                ->andWhere(
                    $qb->expr()->eq('table_schema', $qb->createNamedParameter($databaseName)),
                    $qb->expr()->eq('table_name', $qb->createNamedParameter($table))
                )
                ->executeQuery()->fetchAssociative();

            $status[$table] = [
                'size' => GeneralUtility::formatSize($result['data_length'] + $result['index_length'], 'si') . 'B',
                'rows' => $result['table_rows'],
            ];
        }

        return $status;
    }
}
