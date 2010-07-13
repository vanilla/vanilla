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
</ul>
<?php echo $this->Form->Close('Save');