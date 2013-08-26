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
      $Title = T('Verified Description', 'Verified users bypass spam and pre-moderation filters.');
      $Url = "/user/verify.json?userid=$UserID&verified=0";
   } else {
      $Label = T('Not Verified');
      $Title = T('Not Verified Description', 'Unverified users are passed thru any enabled spam and pre-moderation filters.');
      $Url = "/user/verify.json?userid=$UserID&verified=1";
   }
   
   if (Gdn::Session()->CheckPermission('Garden.Moderation.Manage')) {
      return Anchor($Label, $Url, array('title' => $Title, 'class' => 'User-Verified Hijack'));
   } else {
      return Wrap($Label, 'span', array('title' => $Title, 'class' => 'User-Verified'));
   }
}

endif;