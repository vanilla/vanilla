<?php if (!defined('APPLICATION')) exit();
$User = GetValue('User', Gdn::Controller());
if (!$User && Gdn::Session()->IsValid()) {
   $User = Gdn::Session()->User;
}

if (!$User)
   return;

if ($User->Photo != '') {
?>
   <div class="Photo <?php echo GetValue('_CssClass', $User); ?>">
      <?php
      if (StringBeginsWith($User->Photo, 'http'))
         $Img = Img($User->Photo, array('class' => 'ProfilePhotoLarge'));
      else
         $Img = Img(Gdn_Upload::Url(ChangeBasename($User->Photo, 'p%s')), array('class' => 'ProfilePhotoLarge'));
         
      if (Gdn::Session()->UserID == $User->UserID || Gdn::Session()->CheckPermission('Garden.Users.Edit'))
         echo Anchor(Wrap(T('Change Picture')).$Img, '/profile/picture?userid='.$User->UserID, 'ChangePicture');
      else
         echo $Img;
      ?>
   </div>
<?php } else if ($User->UserID == Gdn::Session()->UserID || Gdn::Session()->CheckPermission('Garden.Users.Edit')) { ?>
   <div class="Photo"><?php echo Anchor(T('Add a Profile Picture'), '/profile/picture?userid='.$User->UserID, 'AddPicture BigButton'); ?></div>
<?php
}
