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
     * @return \Twig_Environment
     */
    protected static function twigInit() {
        $loader = new \Twig_Loader_Filesystem(self::$twigDefaultFolder);
        if (\Gdn::config('Debug') === true) {
            $twigEnv = new \Twig_Environment($loader, ['debug' => true]);
            $twigEnv->addExtension(new \Twig_Extension_Debug());
        } else {
            $twigEnv = new \Twig_Environment($loader);
        }
        self::addAvailableMethods($twigEnv);
        return $twigEnv;
    }

    /**
     * Add a few required method into the twig environment.
     *
     * @param \Twig_Environment $twig
     */
    private static function addAvailableMethods(\Twig_Environment $twig) {
        $twig->addFunction(new \Twig_Function('t', [\Gdn::class, 'translate']));
        $twig->addFunction(new \Twig_Function('url', 'url'));
    }
}
