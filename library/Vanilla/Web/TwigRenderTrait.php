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
     * Initialize the twig environment.
     */
    private function prepareTwig(): \Twig\Environment {
        $loader = new \Twig\Loader\FilesystemLoader(self::$twigDefaultFolder);

        $isDebug = \Gdn::config('Debug') === true;
        $envArgs = [
            'cache' => PATH_CACHE . '/twig',
            'debug' => $isDebug,
            // Automatically controlled by the debug value.
            // This causes twig to check the FS timestamp before going to cache.
            // It will rebuild that file's cache if an update had occured.
            // 'auto_reload' => $isDebug
            'strict_variables' => $isDebug, // Surface template errors in debug mode.
        ];
        $environment = new \Twig\Environment($loader, $envArgs);

        if ($isDebug) {
            $environment->addExtension(new \Twig\Extension\DebugExtension());
        }

        /** @var TwigEnhancer $enhancer */
        $enhancer = \Gdn::getContainer()->get(TwigEnhancer::class);
        $enhancer->enhanceEnvironment($environment);
        $enhancer->enhanceFileSystem($loader);
        return $environment;
    }

    /**
     * @var \Twig\Environment
     */
    private $twig;

    /**
     * Render a given view using twig.
     *
     * @param string $path The view path.
     * @param array $data The data to render.
     *
     * @return \Twig\Markup The rendered HTML in a twig wrapper. Casts to string to unwrap.
     */
    public function renderTwig(string $path, array $data): \Twig\Markup {
        if (!$this->twig) {
            $this->twig = $this->prepareTwig();
        }
        // Ensure that we don't duplicate our root path in the path view.
        $path = str_replace(PATH_ROOT, '', $path);
        return new \Twig\Markup($this->twig->render($path, $data), 'utf-8');
    }
}
