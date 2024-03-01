<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use Xima\XimaApiClient\Configuration;

defined('TYPO3') or die('Access denied.');
call_user_func(function () {
    /**
     * Default TypoScript for API Client Extension
     */
    ExtensionManagementUtility::addStaticFile(
        Configuration::EXT_KEY,
        'Configuration/TypoScript',
        'API Client Extension'
    );
});
