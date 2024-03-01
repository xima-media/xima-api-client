<?php

namespace Xima\XimaApiClient\PreviewRenderer;

use TYPO3\CMS\Backend\Preview\StandardContentPreviewRenderer;
use TYPO3\CMS\Backend\Routing\UriBuilder as BackendUriBuilder;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridColumnItem;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Configuration\BackendConfigurationManager;
use Xima\XimaApiClient\Domain\Repository\ReusableRequestRepository;
use Xima\XimaApiClient\Helper\RequestHelper;

class BackendPreviewRenderer extends StandardContentPreviewRenderer
{
    public function renderPageModulePreviewContent(GridColumnItem $item): string
    {
        $templatePreview = null;
        $html = '';
        $row = $item->getRecord();

        $uid = $row['tx_ximaapiclient_request'];
        $template = $row['tx_ximaapiclient_template'];

        if (!$uid || !$template) {
            return '';
        }

        $reusableRequestRepository = GeneralUtility::makeInstance(ReusableRequestRepository::class);
        $reusableRequest = $reusableRequestRepository->findByUid($uid);
        $backendUriBuilder = GeneralUtility::makeInstance(BackendUriBuilder::class);
        $link = (string)$backendUriBuilder->buildUriFromRoute('web_api_client.Backend\Request_editRequest', [
            'request' => $uid,
        ]);

        $backendConfigurationManager = GeneralUtility::makeInstance(BackendConfigurationManager::class);
        $typoscript = $backendConfigurationManager->getTypoScriptSetup();
        $registeredTemplates = $typoscript['plugin.']['tx_ximaapiclient.']['settings.']['registeredRequestTemplates.'];
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $requestHelper = GeneralUtility::makeInstance(RequestHelper::class, $connectionPool);

        foreach ($registeredTemplates as $registeredTemplate) {
            if ($registeredTemplate['template'] === $template) {
                $templatePreview = $registeredTemplate['preview'] ?? null;
            }
        }

        $templatePreviewMarkup = $templatePreview ? '<img src="' . PathUtility::getPublicResourceWebPath($templatePreview) . '" width="300" />' : '';

        if ($reusableRequest) {
            $html = '<h4>API Client - <em>Reusable Request</em></h4>' .
                '<table>' .
                '<tr><td style="min-width: 150px">Request</td><td><a style="text-decoration: underline;" href="' . $link . '">' . $reusableRequest->getName() . '</a>' . ($reusableRequest->getDescription() ? ' <br/><small>' . $reusableRequest->getDescription() . '</small>' : '') . '</td></tr>' .
                '<tr><td>Template</td><td><code>' . $template . '</code></td></tr>';

            if ($templatePreviewMarkup) {
                $html .= '<tr><td>Template Preview</td><td>' . $templatePreviewMarkup . '</td></tr>';
            }

            if ($detailPid = $row['tx_ximaapiclient_detail_pid']) {
                $link = (string)$backendUriBuilder->buildUriFromRoute('web_layout', [
                    'id' => $detailPid,
                ]);
                $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
                $page = $pageRepository->getPage($detailPid, false);
                $html .= '<tr><td>Detail PID</td><td><a style="text-decoration: underline;" href="' . $link . '">' . $page['title'] . ' (' . $detailPid . ')</a></td></tr>';
            }

            if ($overrides = $requestHelper->getParameterOverrides($row['uid'])) {
                $html .= '<tr><td>Overridable Parameters</td><td><ul style="margin:0;">';

                foreach ($overrides as $override) {
                    $parameterData = [];

                    if ($override['allowGetOverride'] ?? false) {
                        $parameterData[] = 'GET-Parameter "xac_' . $override['parameter'] . '"';
                    }

                    if ($override['overrideByStaticValue'] ?? false) {
                        $parameterData[] = 'Static value "' . $override['staticOverrideValue'] . '"';
                    }

                    $html .= '<li><code>' . $override['parameter'] . (empty($parameterData) ? '' : ' (' . implode(', ', $parameterData) . ')') . '</code></li>';
                }

                $html .= '</ul></td></tr>';
            }

            if ($type = $row['tx_sitepackage_type']) {
                $html .= '<tr><td>Type</td><td>' . $type . '</td></tr>';
            }

            if ($sortRandom = $row['tx_sitepackage_sort_random']) {
                $html .= '<tr><td>Sort random</td><td>✓</td></tr>';
            }

            if ($count = $row['tx_sitepackage_count']) {
                $html .= '<tr><td>Count</td><td>' . $count . '</td></tr>';
            }

            $html .= '</table>';
        }

        return $this->linkEditContent($html, $row);
    }
}
