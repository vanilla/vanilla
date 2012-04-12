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
         echo $this->Form->Label('Type', 'BanType');
         echo $this->Form->DropDown('BanType', $this->Data('_BanTypes'));
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->Label('Value or Pattern', 'BanValue');
         echo $this->Form->TextBox('BanValue');
      ?>
      <span>Use asterisks for wildcards, e.g. &lsquo;*@hotmail.com&rsquo;</span>
   </li>
   <li>
      <?php
         echo $this->Form->Label('Notes', 'Notes');
         echo $this->Form->TextBox('Notes');
      ?>
      <span>Optional</span>
   </li>
   <?php $this->FireEvent("AfterAddBanForm"); ?>
</ul>
<?php echo $this->Form->Close('Save'); ?>