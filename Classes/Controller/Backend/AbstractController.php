<?php

declare(strict_types=1);

namespace Xima\XimaApiClient\Controller\Backend;

use TYPO3\CMS\Backend\Attribute\Controller;
use TYPO3\CMS\Backend\Controller\ClearPageCacheController;
use TYPO3\CMS\Backend\Routing\UriBuilder as BackendUriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use Xima\XimaApiClient\ApiClient\ApiClient;
use Xima\XimaApiClient\ApiClient\ApiSchema;
use Xima\XimaApiClient\Domain\Repository\ReusableRequestRepository;
use Xima\XimaApiClient\Helper\CacheHelper;
use Xima\XimaApiClient\Service\FilterService;

#[Controller]
class AbstractController extends ActionController
{
    protected ModuleTemplate $moduleTemplate;

    public function __construct(
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected readonly PageRenderer $pageRenderer,
        protected readonly IconFactory $iconFactory,
        protected readonly BackendUriBuilder $backendUriBuilder,
        protected readonly ApiSchema $apiSchema,
        protected readonly ApiClient $apiClient,
        protected readonly ReusableRequestRepository $reusableRequestRepository,
        protected readonly CacheHelper $cacheHelper,
        protected readonly FilterService $filterService,
        protected readonly ClearPageCacheController $clearPageCacheController,
        protected readonly ConnectionPool $connectionPool
    ) {
    }

    public function initializeAction(): void
    {
        $this->moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $this->moduleTemplate->setTitle('API Client');
        $this->moduleTemplate->setFlashMessageQueue($this->getFlashMessageQueue());
    }

    /**
     * Generate the button bar for the backend module
     *
     * @param string|null $routeIdentifier
     * @param string|null $displayName
     */
    protected function generateButtonBar(string $routeIdentifier = null, string $displayName = null, array $arguments = []): ButtonBar
    {
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();

        $addExploreButton = $buttonBar->makeLinkButton()
            ->setIcon($this->iconFactory->getIcon('install-manage-features', Icon::SIZE_SMALL))
            ->setTitle('Explore API')
            ->setShowLabelText(true)
            ->setHref((string)$this->backendUriBuilder->buildUriFromRoute('web_api_client.Backend\Explore_explore', [
                'returnUrl' => $this->request->getAttribute('normalizedParams')?->getRequestUri(),
            ]));
        $buttonBar->addButton($addExploreButton);

        $addSchemaButton = $buttonBar->makeLinkButton()
            ->setIcon($this->iconFactory->getIcon('install-documentation', Icon::SIZE_SMALL))
            ->setTitle('API Specification')
            ->setShowLabelText(true)
            ->setHref((string)$this->backendUriBuilder->buildUriFromRoute('web_api_client.Backend\Explore_schema', [
                'returnUrl' => $this->request->getAttribute('normalizedParams')?->getRequestUri(),
            ]));
        $buttonBar->addButton($addSchemaButton);

        $addExploreButton = $buttonBar->makeLinkButton()
            ->setIcon($this->iconFactory->getIcon('install-manage-settings', Icon::SIZE_SMALL))
            ->setTitle('Reusable Request')
            ->setShowLabelText(true)
            ->setHref((string)$this->backendUriBuilder->buildUriFromRoute('web_api_client.Backend\Request_listReusableRequest', [
                'returnUrl' => $this->request->getAttribute('normalizedParams')?->getRequestUri(),
            ]));
        $buttonBar->addButton($addExploreButton);

        $cacheButton = $buttonBar->makeLinkButton()
            ->setIcon($this->iconFactory->getIcon('install-clear-cache', Icon::SIZE_SMALL))
            ->setTitle('Response cache')
            ->setShowLabelText(true)
            ->setHref((string)$this->backendUriBuilder->buildUriFromRoute('web_api_client.Backend\Cache_cache', [
                'returnUrl' => $this->request->getAttribute('normalizedParams')?->getRequestUri(),
            ]));
        $buttonBar->addButton($cacheButton);

        if ($routeIdentifier && $displayName) {
            $shortcutButton = $buttonBar->makeShortcutButton()
                ->setRouteIdentifier($routeIdentifier)
                ->setArguments($arguments)
                ->setDisplayName($displayName);
            $buttonBar->addButton($shortcutButton, ButtonBar::BUTTON_POSITION_RIGHT);
        }

        return $buttonBar;
    }
}
