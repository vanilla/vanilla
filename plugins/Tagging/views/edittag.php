<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo T('Edit Tag'); ?></h1>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<ul>
   <li>
      <?php
         echo $this->Form->Label('Tag Name', 'Name');
         echo $this->Form->TextBox('Name');
      ?>
   </li>
   <?php if ($this->Data('MergeTagVisible')): ?>
   <li>
      <?php
         echo $this->Form->CheckBox('MergeTag', 'Merge this tag with the existing one');
      ?>
   </li>
   <?php endif; ?>
</ul>
<?php echo $this->Form->Close('Save');