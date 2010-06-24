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
   <li id="UrlCode">
		<?php
		echo Wrap(T('Category Url:'), 'strong');
		echo ' ';
		echo Gdn::Request()->Url('category', TRUE);
		echo '/';
		echo Wrap($this->Form->GetValue('UrlCode'));
		echo $this->Form->TextBox('UrlCode');
		echo '/';
		echo Anchor(T('edit'), '#', 'Edit');
		echo Anchor(T('OK'), '#', 'Save SmallButton');
		?>
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
		if(count($this->PermissionData) > 0) {
         if (!$this->Category->AllowDiscussions) {
            echo T('This is a parent category that does not allow discussions.');
         } else {
            echo T('Check all permissions that apply for each role');
            echo $this->Form->CheckBoxGridGroups($this->PermissionData, 'Permission');
         }
		}
      ?>
   </li>
</ul>
<?php echo $this->Form->Close('Save'); ?>