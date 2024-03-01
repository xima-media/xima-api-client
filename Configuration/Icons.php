<?php

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;
use Xima\XimaApiClient\Configuration;

return [
    'backend-modul-api-client' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:' . Configuration::EXT_KEY . '/Resources/Public/Icons/api-module.svg',
    ],
    'plugin-api-client' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:' . Configuration::EXT_KEY . '/Resources/Public/Icons/api-plugin.svg',
    ],
];
