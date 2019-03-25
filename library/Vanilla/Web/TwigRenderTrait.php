<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

/**
 * Class for rendering twig views with the vanilla environment configured.
 */
trait TwigRenderTrait {
    use \Garden\TwigTrait;

    /**
     * Initialize the twig environment.
     */
    public function prepareTwig(): \Twig\Environment {
        $twig = self::twigInit();
        $this->enhanceTwig($twig);
        return $twig;
    }

    /**
     * Render a given view using twig.
     *
     * @param string $path The view path.
     * @param array $data The data to render.
     *
     * @return string The rendered HTML.
     */
    protected function renderTwig(string $path, array $data): string {
        /** @var \Twig\Environment $twig */
        static $twig;
        if (!$twig) {
            $twig = $this->prepareTwig();
        }
        // Ensure that we don't duplicate our root path in the path view.
        $path = str_replace(PATH_ROOT, '', $path);

        // We need to echo instead of return returning because \Gdn_Controller::fetchView()
        // uses only ob_start and ob_get_clean to gather the rendered result.
        return $twig->render($path, $data);
    }

    /**
     * Add a few required method into the twig environment.
     *
     * @param \Twig\Environment $twig The twig environment to enhance.
     */
    private function enhanceTwig(\Twig\Environment $twig) {
        $twig->addFunction(new \Twig_Function('t', [\Gdn::class, 'translate']));
        $twig->addFunction(new \Twig_Function('sanitizeUrl', [\Gdn_Format::class, 'sanitizeUrl']));
        $twig->addFunction(new \Twig_Function('url', 'url'));
    }
}
