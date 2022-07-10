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

    /** @var string The path to look for twig views in. */
    protected static $twigDefaultFolder = PATH_ROOT;

    /**
     * @var \Twig\Environment
     */
    private $twig;

    /**
     * Initialize the twig environment.
     */
    private function prepareTwig(): \Twig\Environment {
        return \Gdn::getContainer()->get(TwigRenderer::class);
    }

    /**
     * Render a given view using twig.
     *
     * @param string $path The view path.
     * @param array $data The data to render.
     *
     * @return string Rendered HTML.
     */
    public function renderTwig(string $path, array $data): string {
        if (!$this->twig) {
            $this->twig = $this->prepareTwig();
        }
        // Ensure that we don't duplicate our root path in the path view.
        $path = str_replace(PATH_ROOT, '', $path);
        return $this->twig->render($path, $data);
    }

    /**
     * Render a
     *
     * @param string $templateString
     * @param array $data
     * @return string
     */
    public function renderTwigFromString(string $templateString, array $data): string {
        if (!$this->twig) {
            $this->twig = $this->prepareTwig();
        }

        $template = $this->twig->createTemplate($templateString);
        return $template->render($data);
    }
}
