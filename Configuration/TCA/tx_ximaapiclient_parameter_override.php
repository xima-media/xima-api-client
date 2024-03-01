<?php

use Xima\XimaApiClient\Configuration;

return [
    'ctrl' => [
        'title' => 'LLL:EXT:' . Configuration::EXT_KEY . '/Resources/Private/Language/locallang_be.xlf:tx_ximaapiclient_parameter_overrides',
        'label' => 'parameter',
        'label_alt' => 'link,fe_user',
        'delete' => 'deleted',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'typeicon_classes' => [
            'default' => 'content-text',
        ],
        'searchFields' => 'parameter',
        'hideTable' => true,
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l18n_parent',
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => 'parameter,allowGetOverride,overrideByStaticValue,staticOverrideValue',
        ],
    ],
    'columns' => [
        'sys_language_uid' => [
            'label' => 'Language',
            'config' => [
                'type' => 'language',
            ],
        ],
        'hidden' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
            ],
        ],
        'sorting' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'parameter' => [
            'exclude' => true,
            'label' => 'LLL:EXT:' . Configuration::EXT_KEY . '/Resources/Private/Language/locallang_be.xlf:tx_ximaapiclient_parameter_overrides.parameter',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'itemsProcFunc' => 'Xima\XimaApiClient\Helper\ItemsProcFunc->getAvailableRequestParameters',
            ],
        ],
        'allowGetOverride' => [
            'exclude' => true,
            'label' => 'LLL:EXT:' . Configuration::EXT_KEY . '/Resources/Private/Language/locallang_be.xlf:tx_ximaapiclient_parameter_overrides.allowGetOverride',
            'description' => 'LLL:EXT:' . Configuration::EXT_KEY . '/Resources/Private/Language/locallang_be.xlf:tx_ximaapiclient_parameter_overrides.allowGetOverride.description',
            'config' => [
                'type' => 'check',
            ],
        ],
        'overrideByStaticValue' => [
            'exclude' => true,
            'label' => 'LLL:EXT:' . Configuration::EXT_KEY . '/Resources/Private/Language/locallang_be.xlf:tx_ximaapiclient_parameter_overrides.overrideByStaticValue',
            'onChange' => 'reload',
            'config' => [
                'type' => 'check',
            ],
        ],
        'staticOverrideValue' => [
            'exclude' => true,
            'displayCond' => 'FIELD:overrideByStaticValue:REQ:true',
            'label' => 'LLL:EXT:' . Configuration::EXT_KEY . '/Resources/Private/Language/locallang_be.xlf:tx_ximaapiclient_parameter_overrides.staticOverrideValue',
            'config' => [
                'type' => 'input',
            ],
        ],
    ],
];
