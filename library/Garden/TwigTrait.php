<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Garden;

/**
 * For classes that need to cache some static values and configs.
 *
 */
trait TwigTrait {

    /** @var string The path to look for twig views in. */
    protected static $twigDefaultFolder = PATH_ROOT;

    /**
     * Twig templating environment initialize
     *
     * @return \Twig\Environment
     */
    protected static function twigInit() {
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

        return $environment;
    }
}
