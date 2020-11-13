<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

use Twig\Loader\LoaderInterface;
use Vanilla\Contracts\ConfigurationInterface;

/**
 * Class for rendering twig views.
 */
class TwigRenderer extends \Twig\Environment {

    /**
     * DI.
     *
     * @param TwigEnhancer $enhancer
     * @param ConfigurationInterface $configuration
     */
    public function __construct(TwigEnhancer $enhancer, ConfigurationInterface $configuration) {
        $loader = new \Twig\Loader\FilesystemLoader(PATH_ROOT);

        $isDebug = $configuration->get('Debug') === true;
        $envArgs = [
            'cache' => $isDebug ? false : $enhancer->getCompileCacheDirectory() ?? false, // Null not allowed. Only false or string.
            'debug' => $isDebug,
            // Automatically controlled by the debug value.
            // This causes twig to check the FS timestamp before going to cache.
            // It will rebuild that file's cache if an update had occured.
            // 'auto_reload' => $isDebug
            'strict_variables' => $isDebug, // Surface template errors in debug mode.
        ];
        parent::__construct($loader, $envArgs);

        if ($isDebug) {
            $this->addExtension(new \Twig\Extension\DebugExtension());
        }

        $enhancer->enhanceEnvironment($this);
        $enhancer->enhanceFileSystem($loader);
    }
}
