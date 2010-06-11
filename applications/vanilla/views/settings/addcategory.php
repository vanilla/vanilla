<?php if (!defined('APPLICATION')) exit();

echo $this->Form->Open();
echo $this->Form->Errors();
?>
<h1><?php echo T('Add Category'); ?></h1>
<ul>
   <li>
      <div class="Info"><?php
         echo Wrap(T('<strong>Categories</strong> allow you to organize your discussions. Categories can only contain discussions.'), 'div');
         echo Wrap(T('<strong>Parent categories</strong> allow you to organize your categories. Parent categories can only contain categories.'), 'div');
      ?></div>
      <?php
         echo $this->Form->CheckBox('IsParent', 'Make this a parent category', array('value' => '1'));
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->Label('Category', 'Name');
         echo $this->Form->TextBox('Name');
      ?>
   </li>
   <li id="UrlCode">
      <?php echo $this->Form->Label('Url Code', 'UrlCode'); ?>
      <div class="Info">
         <?php echo T('The "Url Code" is used to identify the category. It can only contain letters, numbers, underscores, and dashes. It must be unique.'); ?>
      </div>
      <?php echo $this->Form->TextBox('UrlCode'); ?>
   </li>
   <li>
      <?php
         echo $this->Form->Label('Description', 'Description');
         echo $this->Form->TextBox('Description', array('MultiLine' => TRUE));
      ?>
   </li>
	
	<?php if(count($this->PermissionData) > 0) { ?>
   <li id="Permissions">
      <?php
         echo T('Check all permissions that apply for each role');
         echo $this->Form->CheckBoxGridGroups($this->PermissionData, 'Permission');
      ?>
   </li>
	<?php } ?>
</ul>
<?php echo $this->Form->Close('Save'); ?>