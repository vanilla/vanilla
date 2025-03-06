<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

use Garden\JsonFilterTrait;
use Vanilla\ApiUtils;

/**
 * Render a twig template form a static context.
 * Classes should prefer using `TwigRenderTrait`.
 */
class TwigStaticRenderer
{
    use TwigRenderTrait;

    /**
     * Render a twig template from a string.
     *
     * @param string $template
     * @param array $viewData
     *
     * @return \Twig\Markup
     */
    public static function renderString(string $template, array $viewData): \Twig\Markup
    {
        /** @var TwigStaticRenderer $selfInstance */
        $selfInstance = \Gdn::getContainer()->get(TwigStaticRenderer::class);
        return new \Twig\Markup($selfInstance->renderTwigFromString($template, $viewData), "utf-8");
    }

    /**
     * Render a twig template form a static context.
     * Classes should prefer using `TwigRenderTrait`.
     *
     * @param string $viewPath
     * @param array $viewData
     *
     * @return \Twig\Markup HTML in twig markup wrapper. Casts to string to unwrap.
     */
    public static function renderTwigStatic(string $viewPath, array $viewData): \Twig\Markup
    {
        /** @var TwigStaticRenderer $selfInstance */
        $selfInstance = \Gdn::getContainer()->get(TwigStaticRenderer::class);
        return new \Twig\Markup($selfInstance->renderTwig($viewPath, $viewData), "utf-8");
    }

    /**
     * Render a mount point for a react component.
     *
     * @param string $componentName The name of the react component to mount. (Should be registed in frontend with addComponent())
     * @param array $props The props to pass to the component.
     * @param string|null $cssClass A css class to apply on the container.
     * @param string $htmlContents HMTL contents that will be mounted over.
     *
     * @return string
     */
    public static function renderReactModule(
        string $componentName,
        array $props,
        string $cssClass = null,
        string $htmlContents = "",
        string $htmlTag = "div"
    ): string {
        /** @var TwigStaticRenderer $selfInstance */
        $selfInstance = \Gdn::getContainer()->get(TwigStaticRenderer::class);
        return $selfInstance->renderTwigFromString(
            <<<TWIG
    <{$htmlTag} class="{{ class }}" data-react="{{ component }}" data-props="{{ props|e('html_attr') }}">{{ htmlContents|raw }}</{$htmlTag}>
TWIG
            ,
            [
                "props" => json_encode(ApiUtils::jsonFilter($props), JSON_UNESCAPED_UNICODE),
                "component" => $componentName,
                "class" => trim($cssClass ?? ""),
                "htmlContents" => $htmlContents,
            ]
        );
    }
}
