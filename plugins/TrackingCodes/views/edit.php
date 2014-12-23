<?php if (!defined('APPLICATION')) exit();?>
<h1><?php
   if (($this->Code))
      echo T('Edit Tracking Code');
   else
      echo T('Add Tracking Code');
?></h1>
<?php
echo $this->Form->Open();
echo $this->Form->Hidden('Key');
echo $this->Form->Errors();
?>
<ul>
   <li>
      <?php
         echo $this->Form->Label('Name', 'Name');
         echo $this->Form->TextBox('Name');
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->Label('Code', 'Code');
         echo $this->Form->TextBox('Code', array('MultiLine' => TRUE));
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->CheckBox('Enabled', 'Enable this tracking code', array('value' => '1'));
      ?>
   </li>
</ul>
<?php echo $this->Form->Close('Save');