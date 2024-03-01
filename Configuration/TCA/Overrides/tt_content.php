<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use Xima\XimaApiClient\Configuration;
use Xima\XimaApiClient\PreviewRenderer\BackendPreviewRenderer;

/**
 * Plugins
 */
(static function (): void {
    ExtensionUtility::registerPlugin(
        Configuration::EXT_NAME,
        Configuration::PLUGIN_NAME,
        'LLL:EXT:' . Configuration::EXT_KEY . '/Resources/Private/Language/locallang_be.xlf:plugin.title',
        'EXT:' . Configuration::EXT_KEY . '/Resources/Public/Icons/api-plugin.svg'
    );
})();

/**
 * Columns
 */
$flexFormFile = 'FILE:EXT:' . Configuration::EXT_KEY . '/Configuration/FlexForms/' . Configuration::PLUGIN_NAME . '.xml';
$pluginSignature = strtolower(str_replace('_', '', Configuration::EXT_KEY) . '_' . Configuration::PLUGIN_NAME);
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$pluginSignature] = 'layout,select_key,pages,recursive';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'tx_ximaapiclient_template,tx_ximaapiclient_request,tx_ximaapiclient_detail_pid,tx_ximaapiclient_pagination_active,tx_ximaapiclient_pagination_page_parameter,tx_ximaapiclient_parameter_overrides';

// add request and template selection as flexform as their values weren't stored to db when adding them as a TCA field (bug?)
//ExtensionManagementUtility::addPiFlexFormValue(
//    $pluginSignature,
//    $flexFormFile
//);

$GLOBALS['TCA']['tt_content']['types']['list']['previewRenderer']['ximaapiclient_reusablerequest'] = BackendPreviewRenderer::class;

ExtensionManagementUtility::addTCAcolumns(
    'tt_content',
    [
        'tx_ximaapiclient_template' => [
            'exclude' => true,
            'label' => 'LLL:EXT:' . Configuration::EXT_KEY . '/Resources/Private/Language/locallang_be.xlf:tx_ximaapiclient_template',
            'description' => 'LLL:EXT:' . Configuration::EXT_KEY . '/Resources/Private/Language/locallang_be.xlf:tx_ximaapiclient_template.description',
            'onChange' => 'reload',
            'config' => [
                'type' => 'user',
                'renderType' => 'registeredTemplate',
            ],
        ],
        'tx_ximaapiclient_request' => [
            'exclude' => true,
            'label' => 'LLL:EXT:' . Configuration::EXT_KEY . '/Resources/Private/Language/locallang_be.xlf:tx_ximaapiclient_request',
            'description' => 'LLL:EXT:' . Configuration::EXT_KEY . '/Resources/Private/Language/locallang_be.xlf:tx_ximaapiclient_request.description',
            'onChange' => 'reload',
            'displayCond' => 'FIELD:tx_ximaapiclient_template:REQ:true',
            'config' => [
                'type' => 'user',
                'renderType' => 'reusableRequest',
            ],
        ],
        'tx_ximaapiclient_detail_pid' => [
            'exclude' => true,
            'label' => 'LLL:EXT:' . Configuration::EXT_KEY . '/Resources/Private/Language/locallang_be.xlf:tx_ximaapiclient_detail_pid',
            'displayCond' => 'FIELD:tx_ximaapiclient_request:REQ:true',
            'config' => [
                'type' => 'group',
                'allowed' => 'pages',
                'size' => 1,
                'maxitems' => 1,
                'minitems' => 0,
            ],
        ],
        'tx_ximaapiclient_pagination_active' => [
            'exclude' => true,
            'label' => 'LLL:EXT:' . Configuration::EXT_KEY . '/Resources/Private/Language/locallang_be.xlf:tx_ximaapiclient_pagination',
            'onChange' => 'reload',
            'displayCond' => 'FIELD:tx_ximaapiclient_request:REQ:true',
            'config' => [
                'type' => 'check',
            ],
        ],
        'tx_ximaapiclient_pagination_page_parameter' => [
            'exclude' => true,
            'label' => 'LLL:EXT:' . Configuration::EXT_KEY . '/Resources/Private/Language/locallang_be.xlf:tx_ximaapiclient_pagination_page_parameter',
            'description' => 'LLL:EXT:' . Configuration::EXT_KEY . '/Resources/Private/Language/locallang_be.xlf:tx_ximaapiclient_pagination_page_parameter.description',
            'displayCond' => [
                'AND' => [
                    'FIELD:tx_ximaapiclient_request:REQ:true',
                    'FIELD:tx_ximaapiclient_pagination_active:REQ:true',
                ],
            ],
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'itemsProcFunc' => 'Xima\XimaApiClient\Helper\ItemsProcFunc->getAvailableRequestParameters',
            ],
        ],
        'tx_ximaapiclient_parameter_overrides' => [
            'exclude' => true,
            'label' => 'LLL:EXT:' . Configuration::EXT_KEY . '/Resources/Private/Language/locallang_be.xlf:tx_ximaapiclient_parameter_overrides',
            'description' => 'LLL:EXT:' . Configuration::EXT_KEY . '/Resources/Private/Language/locallang_be.xlf:tx_ximaapiclient_parameter_overrides.description',
            'displayCond' => 'FIELD:tx_ximaapiclient_request:REQ:true',
            'config' => [
                'foreign_field' => 'foreign_uid',
                'foreign_sortby' => 'sorting',
                'foreign_table' => 'tx_ximaapiclient_parameter_override',
                'foreign_table_field' => 'foreign_table',
                'type' => 'inline',
                'appearance' => [
                    'collapseAll' => true,
                    'expandSingle' => true,
                    'useSortable' => true,
                    'enabledControls' => [
                        'dragdrop' => true,
                        'info' => false,
                    ],
                ],
            ],
        ],
    ]
);
