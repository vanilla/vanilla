<?php
/**
 * Manages asset endpoints.
 *
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Handles /asset endpoint.
 */
class AssetController extends DashboardController {

    /**
     * Serve combined CSS assets
     *
     * @param string $themeType Either `desktop` or `mobile`.
     * @param string $filename The basename of the file to serve
     * @since 2.1
     */
    public function css($themeType, $filename) {
        $assetModel = new AssetModel();
        $assetModel->serveCss($themeType, $filename);
    }


}
