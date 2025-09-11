<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Web\JsInterpop;

use Vanilla\Web\TwigRenderTrait;
use Vanilla\Web\TwigStaticRenderer;
use Vanilla\Widgets\LegacyWidgetModule;
use Vanilla\Widgets\React\CombinedPropsWidgetInterface;
use Vanilla\Widgets\React\CombinedPropsWidgetTrait;
use Vanilla\Widgets\React\DefaultSectionTrait;
use Vanilla\Widgets\React\ReactWidget;
use Vanilla\Widgets\React\ReactWidgetInterface;

/**
 * Module using the new events UI.
 *
 * @deprecated You probably want to use layout editor and {@link ReactWidget} instead.
 */
abstract class LegacyReactModule extends LegacyWidgetModule implements
    ReactWidgetInterface,
    CombinedPropsWidgetInterface
{
    use TwigRenderTrait;
    use DefaultSectionTrait;
    use CombinedPropsWidgetTrait;

    /**
     * Optional class for div
     * @return string
     */
    public function cssWrapperClass(): string
    {
        return "";
    }

    /**
     * @return string
     */
    public static function getWidgetIconPath(): string
    {
        return "";
    }

    /**
     * Rendering function.
     *
     * @return string
     */
    public function toString(): string
    {
        try {
            $props = $this->getProps();
            if ($props === null) {
                return "";
            }
            $seoContent = $this->renderSeoHtml($props);
            return TwigStaticRenderer::renderReactModule(
                $this->getComponentName(),
                $this->getProps(),
                $this->cssWrapperClass(),
                "<noscript>{$seoContent}</noscript>"
            );
        } catch (\Garden\Web\Exception\HttpException $e) {
            trigger_error($e->getMessage(), E_USER_NOTICE);
            return "";
        }
    }

    /**
     * @inheritdoc
     * Stub implementation for legacy widgets that are used in Pockets.
     */
    public function renderSeoHtml(array $props): ?string
    {
        return "";
    }

    /**
     * @inheritdoc
     */
    public static function getFragmentClasses(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetGroup(): string
    {
        return "Widgets";
    }
}
