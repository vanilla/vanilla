<?php if (!defined('APPLICATION')) exit(); ?>
<h2><?php echo T('Change My Picture'); ?></h2>
<?php
echo $this->Form->Open(array('enctype' => 'multipart/form-data'));
echo $this->Form->Errors();
?>
<ul>
   <li>
      <p><?php echo T('Select an image on your computer (2mb max)'); ?></p>
      <?php echo $this->Form->Input('Picture', 'file'); ?>
   </li>
</ul>
<div class="Warning"><?php echo T('By uploading a file you certify that you have the right to distribute this picture and that it does not violate the Terms of Service.'); ?></div>
<?php echo $this->Form->Close('Upload');