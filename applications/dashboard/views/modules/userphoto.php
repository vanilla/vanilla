<?php if (!defined('APPLICATION')) exit();
$User = GetValue('User', Gdn::Controller());
if (!$User)
   return;

if ($User->Photo != '') {
?>
   <div class="Photo">
      <?php
      if (StringBeginsWith($User->Photo, 'http'))
         $Img = Img($User->Photo, array('class' => 'ProfilePhotoLarge'));
      else
         $Img = Img(Gdn_Upload::Url(ChangeBasename($User->Photo, 'p%s')), array('class' => 'ProfilePhotoLarge'));
         
      if (Gdn::Session()->UserID == $User->UserID)
         echo Anchor(Wrap(T('Change Picture')).$Img, '/profile/picture/', 'ChangePicture');
      else
         echo $Img;
      ?>
   </div>
<?php } else if ($User->UserID == Gdn::Session()->UserID) { ?>
   <div class="Photo"><?php echo Anchor(Wrap(T('Add a Profile Picture')).$Img, '/profile/picture/', 'AddPicture BigButton'); ?></div>
<?php
}
