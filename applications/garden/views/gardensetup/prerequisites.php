<?php if (!defined('APPLICATION')) exit();

echo $this->Form->Open();
?>
<div class="Title">
   <h1><?php echo T("Problems"); ?></h1>
</div>
<div class="Form">
   <?php echo $this->Form->Errors(); ?>
   <div class="Button">
      <?php echo Anchor('Try Again', '/garden/gardensetup'); ?>
   </div>
</div>
<?php
$this->Form->Close();