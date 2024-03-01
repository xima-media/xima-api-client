<?php

declare(strict_types=1);

namespace Xima\XimaApiClient\EventListener;

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\Buttons\LinkButton;
use TYPO3\CMS\Backend\Template\Components\ModifyButtonBarEvent;
use TYPO3\CMS\Core\Page\PageRenderer;

class ModifyButtonBarEventListener
{
    public function __construct(protected readonly UriBuilder $uriBuilder, private readonly PageRenderer $pageRenderer)
    {
    }

    public function __invoke(ModifyButtonBarEvent $event): void
    {
        $buttons = $event->getButtons();

        if (!isset($buttons['right'])) {
            return;
        }

        foreach ($buttons['right'] as $buttonArray) {
            foreach ($buttonArray as $button) {
                if (!$button instanceof LinkButton || !str_contains((string)$button->getClasses(), 't3js-clear-page-cache')) {
                    continue;
                }

                $this->pageRenderer->loadJavaScriptModule(
                    '@xima/xima-api-client/clear-page-and-request-cache.js'
                );

                $button->setClasses('t3js-clear-page-and-request-cache');
                $button->setTitle('Clear cache for this page (including API Client requests)');

                $button->setHref((string)$this->uriBuilder->buildUriFromRoute('ajax_clear_page_and_request_cache'));
            }
        }

        $event->setButtons(
            $buttons
        );
    }
}
