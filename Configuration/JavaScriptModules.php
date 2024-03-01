<?php

use Xima\XimaApiClient\Configuration;

return [
    'dependencies' => ['backend'],
    'imports' => [
        '@xima/xima-api-client/' => 'EXT:' . Configuration::EXT_KEY . '/Resources/Public/JavaScript/dist/',
    ],
];
