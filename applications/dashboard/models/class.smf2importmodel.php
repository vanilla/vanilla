<?php
/**
 * SZMF2 import model.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Object for doing specific actions to a SMF2 import.
 */
class Smf2ImportModel extends Gdn_Model {

    /** @var ImportModel */
    var $ImportModel = null;

    /**
     * Finalize the import.
     */
    public function afterImport() {
        // Make different sizes of avatars
        $this->processAvatars();
    }

    /**
     * Create different sizes of user photos.
     */
    public function processAvatars() {
        $uploadImage = new Gdn_UploadImage();
        $userData = $this->SQL->select('u.Photo')->from('User u')->get();
        foreach ($userData->result() as $user) {
            try {
                $image = PATH_ROOT.DS.'uploads'.DS.str_replace('userpics', 'attachments', $user->Photo);

                // Check extension length
                $imageExtension = strlen(pathinfo($image, PATHINFO_EXTENSION));

                $imageBaseName = pathinfo($image, PATHINFO_BASENAME) + 1;

                if (!file_exists($image)) {
                    rename(substr($image, 0, -$imageExtension), $image);
                }

                // Make sure the avatars folder exists.
                if (!file_exists(PATH_ROOT.'/uploads/userpics')) {
                    mkdir(PATH_ROOT.'/uploads/userpics');
                }

                // Save the uploaded image in profile size
                if (!file_exists(PATH_ROOT.'/uploads/userpics/p'.$imageBaseName)) {
                    $uploadImage->saveImageAs(
                        $image,
                        PATH_ROOT.'/uploads/userpics/p'.$imageBaseName,
                        Gdn::config('Garden.Profile.MaxHeight'),
                        Gdn::config('Garden.Profile.MaxWidth')
                    );
                }

                // Save the uploaded image in preview size
                /*if (!file_exists(PATH_ROOT.'/uploads/userpics/t'.$ImageBaseName))
                $UploadImage->saveImageAs(
                   $Image,
                   PATH_ROOT.'/uploads/userpics/t'.$ImageBaseName,
                   Gdn::config('Garden.Preview.MaxHeight', 100),
                   Gdn::config('Garden.Preview.MaxWidth', 75)
                );*/

                // Save the uploaded image in thumbnail size
                $thumbSize = Gdn::config('Garden.Thumbnail.Size');
                if (!file_exists(PATH_ROOT.'/uploads/userpics/n'.$imageBaseName)) {
                    $uploadImage->saveImageAs(
                        $image,
                        PATH_ROOT.'/uploads/userpics/n'.$imageBaseName,
                        $thumbSize,
                        $thumbSize,
                        true
                    );
                }
            } catch (Exception $ex) {
                // Suppress exceptions from bubbling up.
            }
        }
    }
}
