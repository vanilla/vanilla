<?php if (!defined('APPLICATION')) exit();
$User = GetValue('User', Gdn::Controller());
if (!$User && Gdn::Session()->IsValid()) {
   $User = Gdn::Session()->User;
}

if (!$User)
   return;

$Photo = $User->Photo;

if ($User->Banned) {
   $BannedPhoto = C('Garden.BannedPhoto', 'http://cdn.vanillaforums.com/images/banned_large.png');
   if ($BannedPhoto)
      $Photo = Gdn_Upload::Url($BannedPhoto);
}
   
if ($Photo) {
?>
   <div class="Photo PhotoWrap PhotoWrapLarge <?php echo GetValue('_CssClass', $User); ?>">
      <?php
      if (IsUrl($Photo))
         $Img = Img($Photo, array('class' => 'ProfilePhotoLarge'));
      else
         $Img = Img(Gdn_Upload::Url(ChangeBasename($Photo, 'p%s')), array('class' => 'ProfilePhotoLarge'));
         
      if (!$User->Banned && C('Garden.Profile.EditPhotos', TRUE) && (Gdn::Session()->UserID == $User->UserID || Gdn::Session()->CheckPermission('Garden.Users.Edit')))
         echo Anchor(Wrap(T('Change Picture')), '/profile/picture?userid='.$User->UserID, 'ChangePicture');
      
      echo $Img;
      ?>
   </div>
<?php } else if ($User->UserID == Gdn::Session()->UserID || Gdn::Session()->CheckPermission('Garden.Users.Edit')) { ?>
   <div class="Photo"><?php echo Anchor(T('Add a Profile Picture'), '/profile/picture?userid='.$User->UserID, 'AddPicture BigButton'); ?></div>
<?php
}
