<?php if (!defined('APPLICATION')) exit();

echo $this->Form->Open();
echo $this->Form->Errors();
?>
<h1><?php echo T('Edit Category'); ?></h1>
<ul>
   <li>
      <?php
         echo $this->Form->Label('Category', 'Name');
         echo $this->Form->TextBox('Name');
      ?>
   </li>
   <?php if ($this->Category->AllowDiscussions) { ?>
   <li>
      <?php echo $this->Form->Label('Url Code', 'UrlCode'); ?>
      <div class="Info">
         <?php echo T('The "Url Code" is used to identify the category. It can only contain letters, numbers, underscores, and dashes. It must be unique.'); ?>
      </div>
      <?php echo $this->Form->TextBox('UrlCode'); ?>
   </li>
   <?php } ?>
   <li>
      <?php
         echo $this->Form->Label('Description', 'Description');
         echo $this->Form->TextBox('Description', array('MultiLine' => TRUE));
      ?>
   </li>
   <li>
      <?php
         if (!$this->Category->AllowDiscussions) {
            echo T('This is a parent category that does not allow discussions.');
         } else {
            echo T('Check all permissions that apply for each role');
            echo $this->Form->CheckBoxGridGroups($this->PermissionData, 'Permission');
         }
      ?>
   </li>
</ul>
<?php echo $this->Form->Close('Save'); ?>