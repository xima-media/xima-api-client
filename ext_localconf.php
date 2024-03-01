<?php

use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Log\Processor\IntrospectionProcessor;
use TYPO3\CMS\Core\Log\Writer\FileWriter;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use Xima\XimaApiClient\Configuration;
use Xima\XimaApiClient\Controller\RequestController;
use Xima\XimaApiClient\Form\Element\RegisteredTemplateElement;
use Xima\XimaApiClient\Form\Element\ReusableRequestElement;

defined('TYPO3') or die('Access denied.');

/**
 * Cache
 */
$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][Configuration::CACHE_IDENTIFIER] ??= [];
$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][Configuration::CACHE_IDENTIFIER]['groups'] ??= ['pages'];

/**
 * Log to a separate file
 */
$GLOBALS['TYPO3_CONF_VARS']['LOG']['xima']['api']['client']['writerConfiguration'] = [
    LogLevel::DEBUG => [
        FileWriter::class => [
            'logFile' => Environment::getVarPath() . '/log/xima_api_client.log',
        ],
    ],
];

/**
 * Append line number and file to log entries
 */
$GLOBALS['TYPO3_CONF_VARS']['LOG']['xima']['api']['client']['processorConfiguration'] = [
    LogLevel::DEBUG => [
        IntrospectionProcessor::class => [],
    ],
];

ExtensionUtility::configurePlugin(
    Configuration::EXT_NAME,
    Configuration::PLUGIN_NAME,
    [
        RequestController::class => 'reusableRequest, reusableRequestAjax',
    ],
    [
        RequestController::class => 'reusableRequest, reusableRequestAjax',
    ]
);

// Register "ac" as global fluid namespace
$GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']['ac'] = ['Xima\\XimaApiClient\\ViewHelpers'];

$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1685089443] = [
    'nodeName' => 'registeredTemplate',
    'priority' => 40,
    'class' => RegisteredTemplateElement::class,
];

$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1685089444] = [
    'nodeName' => 'reusableRequest',
    'priority' => 41,
    'class' => ReusableRequestElement::class,
];
