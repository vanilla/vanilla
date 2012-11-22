<?php if (!defined('APPLICATION')) exit();

/**
 * Object for doing specific actions to a vbulletin import.
 * 
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
 */

class Smf2ImportModel extends Gdn_Model {
   /**
    * @var ImportModel
    */
   var $ImportModel = null;

   public function AfterImport() {
      // Set up the routes to redirect from their older counterparts.
      //$Router = Gdn::Router();
      //$Router->SetRoute('forumdisplay\.php\?f=(\d+)', 'vanilla/categories/$1', 'Permanent');
      //$Router->SetRoute('showthread\.php\?t=(\d+)', 'vanilla/discussion/$1', 'Permanent');
      //$Router->SetRoute('member\.php\?u=(\d+)', 'dashboard/profile/$1/x', 'Permanent');
      // Make different sizes of avatars
      $this->ProcessAvatars();
   }
   
   /**
    * Create different sizes of user photos.
    */
   public function ProcessAvatars() {
      $UploadImage = new Gdn_UploadImage();
      $UserData = $this->SQL->Select('u.Photo')->From('User u')->Get();
      foreach ($UserData->Result() as $User) {
         try {
            $Image = PATH_ROOT . DS . 'uploads' . DS . str_replace('userpics','attachments',$User->Photo);

            // Check extension length
            $ImageExtension = strlen(pathinfo($Image, PATHINFO_EXTENSION));

            $ImageBaseName = pathinfo($Image, PATHINFO_BASENAME)+1;

            if (!file_exists($Image))
                rename(substr($Image,0,-$ImageExtension), $Image);
            
            // Make sure the avatars folder exists.
            if (!file_exists(PATH_ROOT.'/uploads/userpics'))
               mkdir(PATH_ROOT.'/uploads/userpics');

            // Save the uploaded image in profile size
            if (!file_exists(PATH_ROOT.'/uploads/userpics/p'.$ImageBaseName))
            $UploadImage->SaveImageAs(
               $Image,
               PATH_ROOT.'/uploads/userpics/p'.$ImageBaseName,
               Gdn::Config('Garden.Profile.MaxHeight', 1000),
               Gdn::Config('Garden.Profile.MaxWidth', 250)
            );
            
            // Save the uploaded image in preview size
            /*if (!file_exists(PATH_ROOT.'/uploads/userpics/t'.$ImageBaseName))
            $UploadImage->SaveImageAs(
               $Image,
               PATH_ROOT.'/uploads/userpics/t'.$ImageBaseName,
               Gdn::Config('Garden.Preview.MaxHeight', 100),
               Gdn::Config('Garden.Preview.MaxWidth', 75)
            );*/
   
            // Save the uploaded image in thumbnail size
            $ThumbSize = Gdn::Config('Garden.Thumbnail.Size', 40);
            if (!file_exists(PATH_ROOT.'/uploads/userpics/n'.$ImageBaseName))
            $UploadImage->SaveImageAs(
               $Image,
               PATH_ROOT.'/uploads/userpics/n'.$ImageBaseName,
               $ThumbSize,
               $ThumbSize,
               TRUE
            );
            
         } catch (Exception $ex) { }
      }
   }
}