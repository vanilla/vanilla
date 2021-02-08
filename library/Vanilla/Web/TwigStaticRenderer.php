<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

/**
 * Render a twig template form a static context.
 * Classes should prefer using `TwigRenderTrait`.
 */
class TwigStaticRenderer {

    use TwigRenderTrait;

    /**
     * Render a twig template form a static context.
     * Classes should prefer using `TwigRenderTrait`.
     *
     * @param string $viewPath
     * @param array $viewData
     *
     * @return \Twig\Markup HTML in twig markup wrapper. Casts to string to unwrap.
     */
    public static function renderTwigStatic(string $viewPath, array $viewData): \Twig\Markup {
        /** @var TwigStaticRenderer $selfInstance */
        $selfInstance = \Gdn::getContainer()->get(TwigStaticRenderer::class);
        return new \Twig\Markup($selfInstance->renderTwig($viewPath, $viewData), 'utf-8');
    }

    /**
     * Render a mount point for a react component.
     *
     * @param string $componentName The name of the react component to mount. (Should be registed in frontend with addComponent())
     * @param array $props The props to pass to the component.
     * @param string|null $cssClass A css class to apply on the container.
     *
     * @return string
     */
    public static function renderReactModule(string $componentName, array $props, string $cssClass = null): string {
        /** @var TwigStaticRenderer $selfInstance */
        $selfInstance = \Gdn::getContainer()->get(TwigStaticRenderer::class);
        return $selfInstance->renderTwigFromString(
            '<div class="{{ class }}" data-react="{{ component }}" data-props="{{ props }}"></div>',
            [
                'props' => json_encode($props, JSON_UNESCAPED_UNICODE),
                'component' => $componentName,
                'class' => trim($cssClass),
            ]
        );
    }
}
