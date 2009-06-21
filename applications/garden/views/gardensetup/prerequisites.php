<?php if (!defined('APPLICATION')) exit();

echo $this->Form->Open();
?>
<div class="Title">
   <h1><span>Vanilla</span></h1>
   <h2><?php echo Translate("Ooopsy Daisy"); ?></h2>
</div>
<?php
echo $this->Form->Errors();
?>
<div class="Button">
   <?php echo Anchor('Try Again', '/garden/gardensetup'); ?>
</div>
<?php
$this->Form->Close();