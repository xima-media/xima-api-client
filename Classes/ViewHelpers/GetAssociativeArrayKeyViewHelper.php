<?php

declare(strict_types=1);

namespace Xima\XimaApiClient\ViewHelpers;

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * @return string
 */
class GetAssociativeArrayKeyViewHelper extends AbstractViewHelper
{
    /**
     * Initialize arguments
     */
    public function initializeArguments(): void
    {
        $this->registerArgument('value', 'string', '');
        $this->registerArgument('values', 'array', '');
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string
     * @throws \Exception
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext): string
    {
        if (!str_contains((string)$arguments['value'], '[')) {
            return '';
        }
        $parameterName = str_replace('.', '_', explode('[', (string)$arguments['value'])[0]);
        $parameterKey = str_replace(']', '', explode('[', (string)$arguments['value'])[1]);

        return $arguments['values'][$parameterName][$parameterKey];
    }
}
