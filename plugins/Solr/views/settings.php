<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo $this->Data('Title'); ?></h1>
<div class="Warning">
   <?php
   echo T('Warning: This is for advanced users.');
   ?>
</div>
<?php
$Cf = $this->ConfigurationModule;

$Cf->Render();