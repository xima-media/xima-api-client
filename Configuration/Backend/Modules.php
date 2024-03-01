<?php

use Xima\XimaApiClient\Configuration;
use Xima\XimaApiClient\Controller\Backend\CacheController;
use Xima\XimaApiClient\Controller\Backend\ExploreController;
use Xima\XimaApiClient\Controller\Backend\RequestController;

/**
 * Definitions for modules provided by EXT:examples
 */
return [
    'web_api_client' => [
        'parent' => 'web',
        'position' => ['after' => 'web_info'],
        'access' => 'user',
        'workspaces' => '*',
        'iconIdentifier' => 'backend-modul-api-client',
        'inheritNavigationComponentFromMainModule' => false,
        'path' => '/module/apiclient',
        'labels' => 'LLL:EXT:xima_api_client/Resources/Private/Language/Module/locallang_mod.xlf',
        'extensionName' => Configuration::EXT_KEY,
        'controllerActions' => [
            ExploreController::class => [
                'index',
                'explore',
                'schema',
            ],
            RequestController::class => [
                'editRequest',
                'tryTemporaryRequest',
                'listReusableRequest',
                'editReusableRequest',
                'deleteReusableRequest',
                'flushReusableRequestCache',
                'warmupReusableRequestCache',
            ],
            CacheController::class => [
                'index',
                'cache',
            ],
        ],
        'routes' => [
            '_default' => [
                'target' => ExploreController::class . '::index',
            ],
            'explore' => [
                'path' => '/explore',
                'target' => ExploreController::class . '::explore',
            ],
            'schema' => [
                'path' => '/schema',
                'target' => ExploreController::class . '::schema',
            ],
            'editRequest' => [
                'path' => '/editRequest',
                'target' => RequestController::class . '::editRequest',
            ],
            'tryTemporaryRequest' => [
                'path' => '/tryTemporaryRequest',
                'target' => RequestController::class . '::tryTemporaryRequest',
            ],
            'listReusableRequest' => [
                'path' => '/listReusableRequest',
                'target' => RequestController::class . '::listReusableRequest',
            ],
            'editReusableRequest' => [
                'path' => '/editReusableRequest',
                'target' => RequestController::class . '::editReusableRequest',
            ],
            'deleteReusableRequest' => [
                'path' => '/deleteReusableRequest',
                'target' => RequestController::class . '::deleteReusableRequest',
            ],
            'flushReusableRequestCache' => [
                'path' => '/flushReusableRequestCache',
                'target' => RequestController::class . '::flushReusableRequestCache',
            ],
            'warmupReusableRequestCache' => [
                'path' => '/warmupReusableRequestCache',
                'target' => RequestController::class . '::warmupReusableRequestCache',
            ],
            'cache' => [
                'path' => '/cache',
                'target' => CacheController::class . '::cache',
            ],
        ],
    ],
];
