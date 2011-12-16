<?php if (!defined('APPLICATION')) exit();
if ($this->User->Photo != '') {
?>
   <div class="Photo">
      <?php
      if (StringBeginsWith($this->User->Photo, 'http'))
         echo Img($this->User->Photo, array('class' => 'ProfilePhotoLarge'));
      else
         echo Img(Gdn_Upload::Url(ChangeBasename($this->User->Photo, 'p%s')), array('class' => 'ProfilePhotoLarge'));
      ?>
   </div>
<?php
}
