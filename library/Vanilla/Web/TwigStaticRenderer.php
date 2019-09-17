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
    public static function renderTwig(string $viewPath, array $viewData): \Twig\Markup {
        /** @var TwigStaticRenderer $selfInstance */
        $selfInstance = \Gdn::getContainer()->get(TwigStaticRenderer::class);
        return $selfInstance->renderTwig($viewPath, $viewData);
    }
}
