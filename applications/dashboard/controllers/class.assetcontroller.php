<?php
/**
 * Manages asset endpoints.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Dashboard
 * @since 2.0
 */

/**
 * Handles /asset endpoint.
 */
class AssetController extends DashboardController {
    /**
     * Delete an image from config. Will attempt to remove any element with the an id that is the slugified
     * config concatenated with '-preview-wrapper'.
     *
     * @param string $config The config value to delete.
     * @throws Gdn_UserException
     */
    public function deleteConfigImage($config = '') {
        $validated = false;
        $deleted = false;
        if (Gdn::request()->isAuthenticatedPostBack(true)) {
            $this->permission('Garden.Settings.Manage');

            if (!$config) {
                return;
            }

            $config = urldecode($config);
            $imagePath = c($config, false);

            if ($imagePath) {
                $ext = pathinfo($imagePath, PATHINFO_EXTENSION);
                if (in_array($ext, ['gif', 'png', 'jpeg', 'jpg', 'bmp', 'tif', 'tiff', 'svg'])) {
                    $validated = true;
                }
            }

            if ($validated) {
                $upload = new Gdn_UploadImage();
                if ($upload->delete($imagePath)) {
                    // For extra safety, ensure an image has been deleted before removing from config.
                    removeFromConfig($config);
                    $deleted = true;
                    $this->informMessage(t('Image deleted.'));
                    $this->jsonTarget('#' . slugify($config) . '-preview-wrapper', '', 'Remove');
                }
            }
        }

        if (!$deleted) {
            $this->informMessage(t('Error deleting image.'));
        }

        $this->redirectTo = '/dashboard/settings';
        $this->render('blank', 'utility', 'dashboard');
    }
}
