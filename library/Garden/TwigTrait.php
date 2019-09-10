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
    protected static $twigDefaultFolder = PATH_ROOT;
    /**
     * Twig templating environment initialize
     *
     * @return \Twig\Environment
     */
    protected static function twigInit() {
        $loader = new \Twig_Loader_Filesystem(self::$twigDefaultFolder);
        if (\Gdn::config('Debug') === true) {
            $twigEnv = new \Twig\Environment($loader, ['debug' => true]);
            $twigEnv->addExtension(new \Twig\Extension\DebugExtension());
        } else {
            $twigEnv = new \Twig\Environment($loader, [
                'cache' => PATH_CACHE . '/twig',
            ]);
        }
        return $twigEnv;
    }
}
