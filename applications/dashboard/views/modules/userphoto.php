<?php if (!defined('APPLICATION')) exit();
if ($this->User->Photo != '') {
?>
   <div class="Photo">
      <?php echo Img(Gdn_Upload::Url(ChangeBasename($this->User->Photo, 'p%s'))); ?>
   </div>
<?php
}
