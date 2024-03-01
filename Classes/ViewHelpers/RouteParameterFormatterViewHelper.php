<?php

declare(strict_types=1);

namespace Xima\XimaApiClient\ViewHelpers;

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * @return string
 */
class RouteParameterFormatterViewHelper extends AbstractViewHelper
{
    /**
     * Initialize arguments
     */
    public function initializeArguments(): void
    {
        $this->registerArgument('route', 'string', 'Route to format, e.g. "/api/{_locale}/campaigns"', true);
        $this->registerArgument('tag', 'string', 'Formatter html tag', required: false, defaultValue: 'mark');
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext): mixed
    {
        $route = $arguments['route'];
        return preg_replace_callback('/{([a-zA-Z_]*)}+/i', fn ($matches) => str_replace(['{', '}'], ['<' . $arguments['tag'] . '>{', '}</' . $arguments['tag'] . '>'], (string)$matches[0]), (string)$route);
    }
}
