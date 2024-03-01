<?php

declare(strict_types=1);

namespace Xima\XimaApiClient\Controller\Backend;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Attribute\Controller;

#[Controller]
final class ExploreController extends AbstractController
{
    public function indexAction(): ResponseInterface
    {
        $this->generateButtonBar('web_api_client', 'API Client', ['action' => 'index']);
        /* ToDo: deprecated functions? https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ExtensionArchitecture/HowTo/BackendModule/CreateModuleWithExtbase.html#create-a-backend-module-with-extbase */
        $this->moduleTemplate->setContent($this->view->render());
        return $this->htmlResponse($this->moduleTemplate->renderContent());
    }

    public function exploreAction(string $clientAlias = null): ResponseInterface
    {
        $clientConfigs = $this->apiClient->getAvailableClientConfigs();

        if (!$clientAlias) {
            $clientAlias = array_keys($clientConfigs)[0];
        }

        $this->apiClient->init($clientAlias);

        $schema = $this->apiSchema->getSchema();
        $schema = $this->apiSchema->resolveReferences($schema);
        // ToDo: move this to template
        $this->view->assignMultiple([
            'schema' => $schema,
            'clientConfigs' => $clientConfigs,
            'clientAlias' => $clientAlias,
        ]);

        $this->generateButtonBar('web_api_client.Backend\Explore_explore', 'API Client - Explore API - ' . $clientAlias, ['clientAlias' => $clientAlias]);

        /* ToDo: deprecated functions? https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ExtensionArchitecture/HowTo/BackendModule/CreateModuleWithExtbase.html#create-a-backend-module-with-extbase */
        $this->moduleTemplate->setContent($this->view->render());
        return $this->htmlResponse($this->moduleTemplate->renderContent());
    }

    public function schemaAction(string $clientAlias = null): ResponseInterface
    {
        $clientConfigs = $this->apiClient->getAvailableClientConfigs();

        if (!$clientAlias) {
            $clientAlias = array_keys($clientConfigs)[0];
        }

        $schemaLocation = $this->apiClient->getClientConfigByAlias($clientAlias)['schemaUrl'];

        $this->apiClient->init($clientAlias);

        $schema = $this->apiSchema->getSchema();

        $this->view->assignMultiple([
            'schema' => $schema,
            'schemaLocation' => $schemaLocation,
            'clientConfigs' => $clientConfigs,
            'clientAlias' => $clientAlias,
        ]);
        $this->generateButtonBar('web_api_client.Backend\Explore_schema', 'API Client - API Specification - ' . $clientAlias, ['clientAlias' => $clientAlias]);

        /* ToDo: deprecated functions? https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ExtensionArchitecture/HowTo/BackendModule/CreateModuleWithExtbase.html#create-a-backend-module-with-extbase */
        $this->moduleTemplate->setContent($this->view->render());
        return $this->htmlResponse($this->moduleTemplate->renderContent());
    }
}
