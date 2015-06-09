<?php
/**
 * SZMF2 import model.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
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
        $this->ProcessAvatars();
    }

    /**
     * Create different sizes of user photos.
     */
    public function processAvatars() {
        $UploadImage = new Gdn_UploadImage();
        $UserData = $this->SQL->select('u.Photo')->from('User u')->get();
        foreach ($UserData->result() as $User) {
            try {
                $Image = PATH_ROOT.DS.'uploads'.DS.str_replace('userpics', 'attachments', $User->Photo);

                // Check extension length
                $ImageExtension = strlen(pathinfo($Image, PATHINFO_EXTENSION));

                $ImageBaseName = pathinfo($Image, PATHINFO_BASENAME) + 1;

                if (!file_exists($Image)) {
                    rename(substr($Image, 0, -$ImageExtension), $Image);
                }

                // Make sure the avatars folder exists.
                if (!file_exists(PATH_ROOT.'/uploads/userpics')) {
                    mkdir(PATH_ROOT.'/uploads/userpics');
                }

                // Save the uploaded image in profile size
                if (!file_exists(PATH_ROOT.'/uploads/userpics/p'.$ImageBaseName)) {
                    $UploadImage->SaveImageAs(
                        $Image,
                        PATH_ROOT.'/uploads/userpics/p'.$ImageBaseName,
                        Gdn::config('Garden.Profile.MaxHeight', 1000),
                        Gdn::config('Garden.Profile.MaxWidth', 250)
                    );
                }

                // Save the uploaded image in preview size
                /*if (!file_exists(PATH_ROOT.'/uploads/userpics/t'.$ImageBaseName))
                $UploadImage->SaveImageAs(
                   $Image,
                   PATH_ROOT.'/uploads/userpics/t'.$ImageBaseName,
                   Gdn::config('Garden.Preview.MaxHeight', 100),
                   Gdn::config('Garden.Preview.MaxWidth', 75)
                );*/

                // Save the uploaded image in thumbnail size
                $ThumbSize = Gdn::config('Garden.Thumbnail.Size', 40);
                if (!file_exists(PATH_ROOT.'/uploads/userpics/n'.$ImageBaseName)) {
                    $UploadImage->SaveImageAs(
                        $Image,
                        PATH_ROOT.'/uploads/userpics/n'.$ImageBaseName,
                        $ThumbSize,
                        $ThumbSize,
                        true
                    );
                }

            } catch (Exception $ex) {
            }
        }
    }
}
