<?php

use Xima\XimaApiClient\Controller\Backend\RequestController;

return [
    'clear_page_and_request_cache' => [
        'path' => '/clear-page-and-request-cache',
        'target' => RequestController::class . '::clearPageAndRequestCacheAction',
    ],
];
