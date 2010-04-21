<?php if (!defined('APPLICATION')) exit();

echo $this->Form->Open();
?>
<div class="Title">
   <div class="Center">
      <div class="Logo"><img src="/applications/dashboard/design/images/vanilla_light.png" /></div>
      <div class="PageName"><h1><?php echo T("PREREQUISITE CHECKLIST"); ?></h1></div>
   </div>
</div>
<div class="Form">
   <?php
      echo $this->Form->Errors(); 
   ?>
   <div class="Button">
      <?php echo Anchor('Try Again', '/dashboard/setup'); ?>
   </div>
</div>
<?php
$this->Form->Close();