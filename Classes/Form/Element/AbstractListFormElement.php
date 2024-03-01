<?php

declare(strict_types=1);

namespace Xima\XimaApiClient\Form\Element;

use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * This class renders a radio selection field with all given elements.
 * Additionally a optionally preview image and description text is rendered.
 * Copied from TYPO3\CMS\Backend\Form\Element\RadioElement
 *
 * https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/User/Index.html
 */
abstract class AbstractListFormElement extends AbstractFormElement
{
    protected function prepareResultArray(array $items, bool $showImages = false, bool $showLinks = false): array
    {
        $languageService = GeneralUtility::makeInstance(LanguageServiceFactory::class)->createFromUserPreferences($GLOBALS['BE_USER']);
        $resultArray = $this->initializeResultArray();

        $disabled = '';
        if ($this->data['parameterArray']['fieldConf']['config']['readOnly'] ?? false) {
            $disabled = ' disabled';
        }

        $fieldInformationResult = $this->renderFieldInformation();
        $fieldInformationHtml = $fieldInformationResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldInformationResult, false);

        $fieldWizardResult = $this->renderFieldWizard();
        $fieldWizardHtml = $fieldWizardResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldWizardResult, false);

        $html = [];

        $html[] = '<div class="formengine-field-item t3js-formengine-field-item form-element-list-wrap">';

        if (!empty($this->data['parameterArray']['fieldConf']['description'])) {
            $fieldInformationText = $this->getLanguageService()->sL($this->data['parameterArray']['fieldConf']['description']);
            if (trim($fieldInformationText) !== '') {
                $html[] = '<div class="form-description">' . nl2br(htmlspecialchars($fieldInformationText)) . '</div>';
            }
        }

        $html[] = $fieldInformationHtml;
        $html[] = '<div style="margin-top:10px;" class="input-group"><span class="input-group-addon"><span class="icon icon-size-small icon-state-default"> <span class="icon-markup"> <svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" viewBox="0 0 16 16"><g class="icon-color"><path d="M13.92 15c-.29 0-.56-.12-.76-.32l-2.89-2.88c-.98.68-2.16 1.04-3.36 1.04C3.65 12.85 1 10.2 1 6.92 1 3.65 3.65 1 6.92 1s5.92 2.65 5.92 5.92c0 1.19-.36 2.37-1.04 3.36l2.89 2.89c.19.19.31.47.31.76 0 .58-.49 1.07-1.08 1.07zm-7-12.58c-2.48 0-4.5 2.02-4.5 4.5s2.02 4.5 4.5 4.5 4.5-2.02 4.5-4.5-2.02-4.5-4.5-4.5z"/></g></svg> </span> </span></span><input type="search" class="t3-form-suggest form-control form-element-list-search" placeholder="Find element"/></div>';
        $html[] = '<div class="form-element-list" style="width:100%;max-height:300px;overflow-y: scroll;background: #fff;margin:-2px 0 10px 0;border: 1px solid #ccc;">';

        ksort($items);
        $html[] = '<div class="form-wizards-wrap">';
        $html[] = '<div class="form-wizards-element">';
        $html[] = '<div class="table-fit" style="margin:0">';
        $html[] = '<table class="table table-transparent table-hover">';
        $html[] = '<tbody>';

        if (empty($items)) {
            $html[] = '<tr><td>No according items found.</td></tr>';
        }

        foreach ($items as $itemId => $item) {
            /* @phpstan-ignore-next-line */
            $label = $languageService->sl($item['label']);
            /* @phpstan-ignore-next-line */
            $description = $languageService->sl($item['description']);
            $value = $item['value'];
            $radioId = htmlspecialchars($this->data['parameterArray']['itemFormElID'] . '_' . rtrim((string)$itemId, '.'));

            $radioElementAttrs = [
                'type' => 'radio',
                'id' => $radioId,
                'value' => $value,
                'class' => 'form-check-input',
                'name' => $this->data['parameterArray']['itemFormElName'],
                ...$this->getOnFieldChangeAttrs('click', $this->data['parameterArray']['fieldChangeFunc'] ?? []),
            ];

            if ((string)$value === (string)$this->data['parameterArray']['itemFormElValue']) {
                $radioElementAttrs['checked'] = 'checked';
            }

            $preview = array_key_exists('preview', $item) && !is_null($item['preview']) ? PathUtility::getPublicResourceWebPath($item['preview']) : null;

            $html[] = '<tr class="form-element-list-item" data-search="' . $label . '">';
            if ($showImages) {
                $html[] = '<td style="width:50px;"><div class="form-element-list-preview" title="Open preview image of the template" data-preview="' . $preview . '" style="background: url(' . $preview . ');background-size: contain;background-position: center;background-repeat: no-repeat;height:50px;width:50px;float:right;"></div></td>';
            }
            $html[] = '<td class="col-checkbox"><input ' . GeneralUtility::implodeAttributes($radioElementAttrs, true) . $disabled . '></td>';
            $html[] = '<td><label for="' . $radioId . '" title="' . $value . '" class="form-element-label">' . $this->appendValueToLabelInDebugMode($label, $value) . '</label></td>';
            $html[] = '<td><div class="small text-muted" style="padding:5px;">' . (array_key_exists('description', $item) ? $description : '') . '</div></td>';

            if ($showLinks) {
                $html[] = '<td style="text-align:right;"><a href="' . $item['url'] . '" title="Open reusable request edit form" target="_blank"><span class="icon icon-size-small icon-state-default"> <span class="icon-markup"> <svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" viewBox="0 0 16 16"><g class="icon-color"><path d="m13.7 3.8-1.4-1.4c-.8-.8-2-.8-2.8 0L5.9 5.9c-.8.8-.8 2 0 2.8l1.2 1.2.9-.8L6.9 8c-.4-.4-.4-1 0-1.4l3.2-3.2c.4-.4 1-.4 1.4 0l1.1 1.1c.4.4.4 1 0 1.4l-1.3 1.3c.2.4.4.9.4 1.4l2-2c.7-.8.7-2.1 0-2.8z"/><path d="m8.9 6.1-.9.8L9.1 8c.4.4.4 1 0 1.4l-3.2 3.2c-.4.4-1 .4-1.4 0l-1.1-1.1c-.4-.4-.4-1 0-1.4l1.3-1.3c-.2-.4-.4-.9-.4-1.4l-2 2c-.8.8-.8 2 0 2.8l1.4 1.4c.8.8 2 .8 2.8 0l3.5-3.5c.8-.8.8-2 0-2.8L8.9 6.1z"/></g></svg> </span> </span></a></td>';
            }
            $html[] = '</tr>';
        }
        $html[] = '</tbody>';
        $html[] = '</table>';
        $html[] = '</div>';
        $html[] = '</div>';
        $html[] = '</div>';
        $html[] = '</div>';
        $html[] = '</div>';
        $html[] = '<style>mark{padding: 0.1875em 0;} .form-element-list-preview:hover{cursor:zoom-in;} .form-element-list-wrap label:hover{cursor:pointer;}</style>';
        $resultArray['html'] = implode(LF, $html);

        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->loadJavaScriptModule('@xima/xima-api-client/FormElement.js');
        return $resultArray;
    }

    protected function appendValueToLabelInDebugMode(string|int $label, string|int $value): string
    {
        if ($value !== '' && $this->getBackendUser()->shallDisplayDebugInformation()) {
            $value = htmlspecialchars((string)$value);
            return trim($label . ' <code>[' . $value . ']</code>');
        }

        return trim((string)$label);
    }
}
