<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
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
        return new \Twig_Environment($loader);
    }
}
