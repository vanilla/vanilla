<?php
/**
 * vBulletin import model.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0.18
 */

/**
 * Object for doing specific actions to a vBulletin import.
 */
class vBulletinImportModel extends Gdn_Model {

    /** @var ImportModel */
    var $ImportModel = null;

    /**
     * Custom finalization.
     *
     * @throws Exception
     */
    public function afterImport() {
        // Set up the routes to redirect from their older counterparts.
        $Router = Gdn::router();

        // Categories
        $Router->SetRoute('forumdisplay\.php\?f=(\d+)', 'categories/$1', 'Permanent');
        $Router->SetRoute('archive\.php/f-(\d+)\.html', 'categories/$1', 'Permanent');

        // Discussions & Comments
        $Router->SetRoute('showthread\.php\?t=(\d+)', 'discussion/$1', 'Permanent');
        //$Router->SetRoute('showthread\.php\?p=(\d+)', 'discussion/comment/$1#Comment_$1', 'Permanent');
        //$Router->SetRoute('showpost\.php\?p=(\d+)', 'discussion/comment/$1#Comment_$1', 'Permanent');
        $Router->SetRoute('archive\.php/t-(\d+)\.html', 'discussion/$1', 'Permanent');

        // Profiles
        $Router->SetRoute('member\.php\?u=(\d+)', 'profile/$1/x', 'Permanent');
        $Router->SetRoute('usercp\.php', 'profile', 'Permanent');
        $Router->SetRoute('profile\.php', 'profile', 'Permanent');

        // Other
        $Router->SetRoute('attachment\.php\?attachmentid=(\d+)', 'discussion/download/$1', 'Permanent');
        $Router->SetRoute('search\.php', 'discussions', 'Permanent');
        $Router->SetRoute('private\.php', 'messages/all', 'Permanent');
        $Router->SetRoute('subscription\.php', 'discussions/bookmarked', 'Permanent');

        // Make different sizes of avatars
        $this->ProcessAvatars();

        // Prep config for ProfileExtender plugin based on imported fields
        $this->ProfileExtenderPrep();

        // Set guests to System user to prevent security issues
        $SystemUserID = Gdn::userModel()->GetSystemUserID();
        $this->SQL->update('Discussion')
            ->set('InsertUserID', $SystemUserID)
            ->where('InsertUserID', 0)
            ->put();
        $this->SQL->update('Comment')
            ->set('InsertUserID', $SystemUserID)
            ->where('InsertUserID', 0)
            ->put();
    }

    /**
     * Create different sizes of user photos.
     */
    public function processAvatars() {
        $UploadImage = new Gdn_UploadImage();
        $UserData = $this->SQL->select('u.Photo')->from('User u')->where('u.Photo is not null')->get();

        // Make sure the avatars folder exists.
        if (!file_exists(PATH_UPLOADS.'/userpics')) {
            mkdir(PATH_UPLOADS.'/userpics');
        }

        // Get sizes
        $ProfileHeight = c('Garden.Profile.MaxHeight', 1000);
        $ProfileWidth = c('Garden.Profile.MaxWidth', 250);
        $ThumbSize = c('Garden.Thumbnail.Size', 40);

        // Temporarily set maximum quality
        saveToConfig('Garden.UploadImage.Quality', 100, false);

        // Create profile and thumbnail sizes
        foreach ($UserData->result() as $User) {
            try {
                $Image = PATH_ROOT.DS.'uploads'.DS.GetValue('Photo', $User);
                $ImageBaseName = pathinfo($Image, PATHINFO_BASENAME);

                // Save profile size
                $UploadImage->SaveImageAs(
                    $Image,
                    PATH_UPLOADS.'/userpics/p'.$ImageBaseName,
                    $ProfileHeight,
                    $ProfileWidth
                );

                // Save thumbnail size
                $UploadImage->SaveImageAs(
                    $Image,
                    PATH_UPLOADS.'/userpics/n'.$ImageBaseName,
                    $ThumbSize,
                    $ThumbSize,
                    true
                );
            } catch (Exception $ex) {
            }
        }
    }

    /**
     * Get profile fields imported and add to ProfileFields list.
     */
    public function profileExtenderPrep() {
        $ProfileKeyData = $this->SQL->select('m.Name')->Distinct()->from('UserMeta m')->like('m.Name', 'Profile_%')->get();
        $ExistingKeys = array_filter((array)explode(',', c('Plugins.ProfileExtender.ProfileFields', '')));
        foreach ($ProfileKeyData->result() as $Key) {
            $Name = str_replace('Profile.', '', $Key->Name);
            if (!in_array($Name, $ExistingKeys)) {
                $ExistingKeys[] = $Name;
            }
        }
        if (count($ExistingKeys)) {
            saveToConfig('Plugins.ProfileExtender.ProfileFields', implode(',', $ExistingKeys));
        }
    }
}
