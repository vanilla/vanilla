<?php if (!defined('APPLICATION')) exit();

if (!function_exists('UserVerified')):
/**
 * Return the verified status of a user with a link to change it.
 * @param array|object $User
 * @return string 
 */
function UserVerified($User) {
   $UserID = GetValue('UserID', $User);
   
   if (GetValue('Verified', $User)) {
      $Label = T('Verified');
      $Title = T('This user has been verified as a non-spammer.');
      $Url = "/user/verify.json?userid=$UserID&verified=0";
   } else {
      $Label = T('Not Verified');
      $Title = T('This user has not been verified as a non-spammer.');
      $Url = "/user/verify.json?userid=$UserID&verified=1";
   }
   
   if (Gdn::Session()->CheckPermission('Garden.Moderation.Manage')) {
      return Anchor($Label, $Url, array('title' => $Title, 'class' => 'User-Verified Hijack'));
   } else {
      return Wrap($Label, 'span', array('title' => $Title, 'class' => 'User-Verified'));
   }
}

endif;