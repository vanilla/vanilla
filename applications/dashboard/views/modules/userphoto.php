<?php if (!defined('APPLICATION')) exit();
if ($this->User->Photo != '') {
?>
   <div class="Photo">
      <?php
      if (strpos($this->User->Photo, 'http') === 0)
         echo Img($this->User->Photo);
      else
         echo Img(Gdn_Upload::Url(ChangeBasename($this->User->Photo, 'p%s')));
      ?>
   </div>
<?php
}
