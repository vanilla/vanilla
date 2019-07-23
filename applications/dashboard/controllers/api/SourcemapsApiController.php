<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\Web\Data;

/**
 * API Controller to deliver source map files for compiled static resources: js, css.
 */
class SourcemapsApiController extends AbstractApiController {

    /**
     * Get source map.
     *
     * @param string $path Relative path to source map file
     * @return Data
     */
    public function get(string $path): Data {
        $sourceMapsEnabled = Gdn::config()->get(
            'Garden.Security.SourceMaps.Enabled',
            Gdn::config()->get('Debug')
        );

        $result = new Data('');
        if ($sourceMapsEnabled) {
            $fullPath = PATH_ROOT.DS.'dist'.DS.$path;
            if (is_file($fullPath)) {
                $result->setData(file_get_contents($fullPath));
            } else {
                $result->setData(t('File not found.').' '.$path);
                $result->setStatus(404);
            }
        } else {
            $result->setStatus(403);
        }
        return $result->setHeader("Content-Type", 'application/json');
    }
}
