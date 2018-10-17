<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
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
     * @return \Twig_Environment
     */
    protected static function twigInit() {
        $loader = new \Twig_Loader_Filesystem(self::$twigDefaultFolder);
        if (\Gdn::config('Debug') === true) {
            $twigEnv  = new \Twig_Environment($loader, ['debug' => true]);
            $twigEnv->addExtension(new \Twig_Extension_Debug());
        } else {
            $twigEnv  = new \Twig_Environment($loader);
        }
        return $twigEnv;
    }
}
