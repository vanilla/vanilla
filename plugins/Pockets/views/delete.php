<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo $this->Data('Title'); ?></h1>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<ul>
   <li>
      <div class="Warning"><?php echo sprintf(T('Are you sure you want to delete this %s?'), strtolower(T('Pocket'))); ?></div>
   </li>
</ul>
<?php echo $this->Form->Close('Delete');