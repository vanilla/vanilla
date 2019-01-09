<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL v2
 */

namespace Garden;

/**
 * For classes which extend Gdn_Module to cache fetchViewLocation response.
 */
trait StaticCacheViewLocationTrait {
    /**
     * @var array $views Saved in static cache view files path
     */
    protected static $views = [];

    /**
     * Method overwrites parent method to cache response statically
     *
     * @param string $view Template name
     * @param string $applicationFolder App folder to check for template
     *
     * @return array|mixed
     */
    public function fetchViewLocation($view = '', $applicationFolder = '') {
        $key = $applicationFolder . ':' . $view;
        if (!array_key_exists($key, self::$views)) {
            $viewPath = parent::fetchViewLocation($view, $applicationFolder);
            self::$views[$key] = $viewPath;
        }
        return self::$views[$key];
    }
}
