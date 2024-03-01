<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use Xima\XimaApiClient\Configuration;

defined('TYPO3') or die('Access denied.');
call_user_func(function () {
    /**
     * Default PageTS for Api Client Extension
     */
    ExtensionManagementUtility::registerPageTSConfigFile(
        Configuration::EXT_KEY,
        'Configuration/TsConfig/Page/All.tsconfig',
        'API Client Extension'
    );
});
