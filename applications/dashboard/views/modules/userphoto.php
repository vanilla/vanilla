<?php if (!defined('APPLICATION')) exit();
if ($this->User->Photo != '') {
?>
   <div class="Photo">
      <?php
      if (StringBeginsWith($this->User->Photo, 'http'))
         $Img = Img($this->User->Photo, array('class' => 'ProfilePhotoLarge'));
      else
         $Img = Img(Gdn_Upload::Url(ChangeBasename($this->User->Photo, 'p%s')), array('class' => 'ProfilePhotoLarge'));
         
      if (Gdn::Session()->UserID == $this->User->UserID)
         echo Anchor(Wrap(T('Change Picture')).$Img, '/profile/picture/', 'ChangePicture');
      else
         echo $Img;
      ?>
   </div>
<?php } else if ($this->User->UserID == Gdn::Session()->UserID) { ?>
   <div class="Photo"><?php echo Anchor(Wrap(T('Add a Profile Picture')).$Img, '/profile/picture/', 'AddPicture BigButton'); ?></div>
<?php
}
