<?php if (!defined('APPLICATION')) exit();

echo $this->Form->Open();
echo $this->Form->Errors();
?>
<h1><?php echo Gdn::Translate('Add Category'); ?></h1>
<ul>
   <li>
      <?php
         echo $this->Form->Label('Category', 'Name');
         echo $this->Form->TextBox('Name');
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->Label('Description', 'Description');
         echo $this->Form->TextBox('Description', array('MultiLine' => TRUE));
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->CheckBox('AllowDiscussions', 'Allow discussions to be posted in this category', array('value' => '1'));
      ?>
   </li>
   <li id="Permissions">
      <?php
         echo Gdn::Translate('Check all permissions that apply for each role');
         echo $this->Form->CheckBoxGridGroups($this->PermissionData, 'Permission');
      ?>
   </li>
</ul>
<?php echo $this->Form->Close('Save'); ?>