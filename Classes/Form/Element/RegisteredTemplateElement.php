<?php

declare(strict_types=1);

namespace Xima\XimaApiClient\Form\Element;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\BackendConfigurationManager;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * This class renders a radio selection field with all registered fluid templates.
 * Additionally a optionally preview image and description text is rendered.
 * Copied from TYPO3\CMS\Backend\Form\Element\RadioElement
 *
 * https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/User/Index.html
 */
class RegisteredTemplateElement extends AbstractListFormElement
{
    public function render(): array
    {
        return $this->prepareResultArray(self::getRegisteredTemplates($this->data), true, );
    }

    public static function getRegisteredTemplates(array $data = []): array
    {
        $backendConfigurationManager = GeneralUtility::makeInstance(BackendConfigurationManager::class);
        $typoscript = $backendConfigurationManager->getTypoScriptSetup();
        $registeredTemplates = $typoscript['plugin.']['tx_ximaapiclient.']['settings.']['registeredRequestTemplates.'];
        $allowedCategories = $data['processedTca']['columns']['tx_ximaapiclient_template']['config']['categories'] ?? [];

        $items = [];

        foreach ($registeredTemplates as $template) {
            if (!empty($allowedCategories) && !in_array($template['category'], $allowedCategories)) {
                continue;
            }

            $items[$template['label']] = [
                'label' => $template['label'],
                'value' => $template['template'],
                'description' => $template['description'],
                'preview' => $template['preview'] ?? null,
                'addToAllRequests' => (bool)$template['addToAllRequests'],
            ];
        }

        usort($items, fn ($a, $b) => strcmp(
            (string)(str_starts_with((string)$a['label'], 'LLL:EXT:') ? LocalizationUtility::translate($a['label']) : $a['label']),
            (string)(str_starts_with((string)$b['label'], 'LLL:EXT:') ? LocalizationUtility::translate($b['label']) : $b['label'])
        ));

        return $items;
    }

    public static function getRegisteredTemplate(string $template): ?array
    {
        $registeredTemplates = self::getRegisteredTemplates();
        return $registeredTemplates[$template] ?? null;
    }

    public static function getRegisteredTemplateByValue(?string $value): ?array
    {
        $registeredTemplates = self::getRegisteredTemplates();
        foreach ($registeredTemplates as $registeredTemplate) {
            if ($registeredTemplate['value'] === $value) {
                return $registeredTemplate;
            }
        }
        return null;
    }
}
