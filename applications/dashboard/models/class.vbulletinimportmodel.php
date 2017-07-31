<?php
/**
 * vBulletin import model.
 *
 * @copyright 2009-2017 Vanilla Forums Inc.
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
        $router = Gdn::router();

        // Categories
        $router->SetRoute('forumdisplay\.php\?f=(\d+)', 'categories/$1', 'Permanent');
        $router->SetRoute('archive\.php/f-(\d+)\.html', 'categories/$1', 'Permanent');

        // Discussions & Comments
        $router->SetRoute('showthread\.php\?t=(\d+)', 'discussion/$1', 'Permanent');
        //$Router->SetRoute('showthread\.php\?p=(\d+)', 'discussion/comment/$1#Comment_$1', 'Permanent');
        //$Router->SetRoute('showpost\.php\?p=(\d+)', 'discussion/comment/$1#Comment_$1', 'Permanent');
        $router->SetRoute('archive\.php/t-(\d+)\.html', 'discussion/$1', 'Permanent');

        // Profiles
        $router->SetRoute('member\.php\?u=(\d+)', 'profile/$1/x', 'Permanent');
        $router->SetRoute('usercp\.php', 'profile', 'Permanent');
        $router->SetRoute('profile\.php', 'profile', 'Permanent');

        // Other
        $router->SetRoute('attachment\.php\?attachmentid=(\d+)', 'discussion/download/$1', 'Permanent');
        $router->SetRoute('search\.php', 'discussions', 'Permanent');
        $router->SetRoute('private\.php', 'messages/all', 'Permanent');
        $router->SetRoute('subscription\.php', 'discussions/bookmarked', 'Permanent');

        // Make different sizes of avatars
        $this->ProcessAvatars();

        // Prep config for ProfileExtender plugin based on imported fields
        $this->ProfileExtenderPrep();

        // Set guests to System user to prevent security issues
        $systemUserID = Gdn::userModel()->GetSystemUserID();
        $this->SQL->update('Discussion')
            ->set('InsertUserID', $systemUserID)
            ->where('InsertUserID', 0)
            ->put();
        $this->SQL->update('Comment')
            ->set('InsertUserID', $systemUserID)
            ->where('InsertUserID', 0)
            ->put();
    }

    /**
     * Create different sizes of user photos.
     */
    public function processAvatars() {
        $uploadImage = new Gdn_UploadImage();
        $userData = $this->SQL->select('u.Photo')->from('User u')->where('u.Photo is not null')->get();

        // Make sure the avatars folder exists.
        if (!file_exists(PATH_UPLOADS.'/userpics')) {
            mkdir(PATH_UPLOADS.'/userpics');
        }

        // Get sizes
        $profileHeight = c('Garden.Profile.MaxHeight');
        $profileWidth = c('Garden.Profile.MaxWidth');
        $thumbSize = c('Garden.Thumbnail.Size');

        // Temporarily set maximum quality
        saveToConfig('Garden.UploadImage.Quality', 100, false);

        // Create profile and thumbnail sizes
        foreach ($userData->result() as $user) {
            try {
                $image = PATH_ROOT.DS.'uploads'.DS.GetValue('Photo', $user);
                $imageBaseName = pathinfo($image, PATHINFO_BASENAME);

                // Save profile size
                $uploadImage->SaveImageAs(
                    $image,
                    PATH_UPLOADS.'/userpics/p'.$imageBaseName,
                    $profileHeight,
                    $profileWidth
                );

                // Save thumbnail size
                $uploadImage->SaveImageAs(
                    $image,
                    PATH_UPLOADS.'/userpics/n'.$imageBaseName,
                    $thumbSize,
                    $thumbSize,
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
        $profileKeyData = $this->SQL->select('m.Name')->Distinct()->from('UserMeta m')->like('m.Name', 'Profile_%')->get();
        $existingKeys = array_filter((array)explode(',', c('Plugins.ProfileExtender.ProfileFields', '')));
        foreach ($profileKeyData->result() as $key) {
            $name = str_replace('Profile.', '', $key->Name);
            if (!in_array($name, $existingKeys)) {
                $existingKeys[] = $name;
            }
        }
        if (count($existingKeys)) {
            saveToConfig('Plugins.ProfileExtender.ProfileFields', implode(',', $existingKeys));
        }
    }
}
