<?php if (!defined('APPLICATION')) exit();

/**
 * Object for doing specific actions to a vbulletin import.
 * 
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
 */

class vBulletinImportModel extends Gdn_Model {
   /**
    * @var ImportModel
    */
   var $ImportModel = null;

   public function AfterImport() {
      // Set up the routes to redirect from their older counterparts.
      $Router = Gdn::Router();
      
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
      $SystemUserID = Gdn::UserModel()->GetSystemUserID();
      $this->SQL->Update('Discussion')
         ->Set('InsertUserID', $SystemUserID)
         ->Where('InsertUserID', 0)
         ->Put();
      $this->SQL->Update('Comment')
         ->Set('InsertUserID', $SystemUserID)
         ->Where('InsertUserID', 0)
         ->Put();
   }
   
   /**
    * Create different sizes of user photos.
    */
   public function ProcessAvatars() {
      $UploadImage = new Gdn_UploadImage();
      $UserData = $this->SQL->Select('u.Photo')->From('User u')->Where('u.Photo is not null')->Get();
      
      // Make sure the avatars folder exists.
      if (!file_exists(PATH_UPLOADS.'/userpics'))
         mkdir(PATH_UPLOADS.'/userpics');
      
      // Get sizes
      $ProfileHeight = C('Garden.Profile.MaxHeight', 1000);
      $ProfileWidth = C('Garden.Profile.MaxWidth', 250);
      $ThumbSize = C('Garden.Thumbnail.Size', 40);
      
      // Temporarily set maximum quality
      SaveToConfig('Garden.UploadImage.Quality', 100, FALSE);
      
      // Create profile and thumbnail sizes
      foreach ($UserData->Result() as $User) {
         try {
            $Image = PATH_ROOT . DS . 'uploads' . DS . GetValue('Photo', $User);
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
               TRUE
            );
         } catch (Exception $ex) { }
      }
   }
   
   /**
    * Get profile fields imported and add to ProfileFields list.
    */
   public function ProfileExtenderPrep() {
      $ProfileKeyData = $this->SQL->Select('m.Name')->Distinct()->From('UserMeta m')->Like('m.Name', 'Profile_%')->Get();
      $ExistingKeys = array_filter((array)explode(',', C('Plugins.ProfileExtender.ProfileFields', '')));
      foreach ($ProfileKeyData->Result() as $Key) {
         $Name = str_replace('Profile.', '', $Key->Name);
         if (!in_array($Name, $ExistingKeys)) {
            $ExistingKeys[] = $Name;
         }
      }
      if (count($ExistingKeys))
         SaveToConfig('Plugins.ProfileExtender.ProfileFields', implode(',', $ExistingKeys));
   }
}