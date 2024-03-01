<?php

declare(strict_types=1);

namespace Xima\XimaApiClient\Form\Element;

use TYPO3\CMS\Backend\Routing\UriBuilder as BackendUriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Xima\XimaApiClient\Domain\Repository\ReusableRequestRepository;

/**
 * This class renders a radio selection field with all registered fluid templates.
 * Additionally a optionally preview image and description text is rendered.
 * Copied from TYPO3\CMS\Backend\Form\Element\RadioElement
 *
 * https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/Type/User/Index.html
 */
class ReusableRequestElement extends AbstractListFormElement
{
    public function render(): array
    {
        $reusableRequestRepository = GeneralUtility::makeInstance(ReusableRequestRepository::class);
        $backendUriBuilder = GeneralUtility::makeInstance(BackendUriBuilder::class);
        $reusableRequests = $reusableRequestRepository->findAll();

        $registeredTemplate = RegisteredTemplateElement::getRegisteredTemplateByValue($this->data['databaseRow']['tx_ximaapiclient_template']);
        if (!$registeredTemplate) {
            return [];
        }

        $items = [];
        foreach ($reusableRequests as $reusableRequest) {
            if (!in_array($registeredTemplate['value'], $reusableRequest->getRegisteredTemplates()) && !$registeredTemplate['addToAllRequests']) {
                continue;
            }
            $items[$reusableRequest->getName()] = [
                'label' => $reusableRequest->getName(),
                'value' => $reusableRequest->getUid(),
                'description' => $reusableRequest->getDescription(),
                'url' =>  (string)$backendUriBuilder->buildUriFromRoute('web_api_client.Backend\Request_editRequest', [
                    'request' => $reusableRequest->getUid(),
                ]),
            ];
        }

        return $this->prepareResultArray($items, false, true);
    }
}
