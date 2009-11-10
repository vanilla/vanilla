<?php if (!defined('APPLICATION')) exit();

echo $this->Form->Open();
echo $this->Form->Errors();
?>
<h1><?php echo Gdn::Translate('Edit Category'); ?></h1>
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
         if (!$this->Category->AllowDiscussions) {
            echo Gdn::Translate('This is a parent category that does not allow discussions.');
         } else {
            echo Gdn::Translate('Check all permissions that apply for each role');
            echo $this->Form->CheckBoxGridGroups($this->PermissionData, 'Permission');
         }
      ?>
   </li>
</ul>
<?php echo $this->Form->Close('Save'); ?>