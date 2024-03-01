<?php

declare(strict_types=1);

namespace Xima\XimaApiClient\ViewHelpers;

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * @return string
 */
class JsonDecodeViewHelper extends AbstractViewHelper
{
    /**
     * Initialize arguments
     */
    public function initializeArguments(): void
    {
        $this->registerArgument('json', 'string', 'String representation of an json object');
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
        $str = null;
        $args = ['json'];
        foreach ($args as $arg) {
            ${$arg} = $arguments[$arg] ?: '';
        }

        /** @phpstan-ignore-next-line */
        if (!$str) {
            $str = $renderChildrenClosure();
        }

        // JavaScript Object (z.B. aus TypoScript-Setup) in JSON konvertieren
        return (string)json_decode((string)$str, true, 512, JSON_THROW_ON_ERROR);
    }
}
