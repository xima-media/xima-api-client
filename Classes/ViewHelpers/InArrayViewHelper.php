<?php

declare(strict_types=1);

namespace Xima\XimaApiClient\ViewHelpers;

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * @return string
 */
class InArrayViewHelper extends AbstractViewHelper
{
    /**
     * Initialize arguments
     */
    public function initializeArguments(): void
    {
        $this->registerArgument('needle', 'string', 'The searched value.');
        $this->registerArgument('haystack', 'array', 'The array.');
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
        if (is_null($arguments['haystack'])) {
            return '';
        }
        return (string)in_array($arguments['needle'], $arguments['haystack']);
    }
}
