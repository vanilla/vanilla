<?php if (!defined('APPLICATION')) exit();
if ($this->User->Photo != '') {
?>
   <div class="Photo">
      <?php
      if (StringBeginsWith($this->User->Photo, 'http'))
         echo Img($this->User->Photo);
      else
         echo Img(Gdn_Upload::Url(ChangeBasename($this->User->Photo, 'p%s')));
      ?>
   </div>
<?php
}
