<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo $this->Data('Title'); ?></h1>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<ul>
   <?php $this->FireEvent("BeforeAddBanForm"); ?>
   <li>
      <?php
         echo $this->Form->Label('BanType', 'Ban Type');
         echo $this->Form->DropDown('BanType', $this->Data('_BanTypes'));
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->Label('Ban Value', 'BanValue');
         echo $this->Form->TextBox('BanValue');
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->Label('Notes', 'Notes');
         echo $this->Form->TextBox('Notes');
      ?>
   </li>
   <?php $this->FireEvent("AfterAddBanForm"); ?>
</ul>
<?php echo $this->Form->Close('Save'); ?>