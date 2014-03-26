<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo $this->Data('Title'); ?></h1>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();

$CountCheckedComments = GetValue('CountCheckedComments', $this->Data, 0);

echo Wrap(sprintf(
   T('Split %s to new discussion', 'You have chosen to split %s into a new discussion.'),
   Plural($CountCheckedComments, '%s comment', '%s comments')
   ), 'p');
?>
<ul>
   <li>
      <?php
         echo $this->Form->Label('New Discussion Topic', 'Name');
         echo $this->Form->TextBox('Name');
      ?>
   </li>
   <?php if ($this->ShowCategorySelector === TRUE) { ?>
   <li>
      <?php
         echo '<p><div class="Category">';
         echo $this->Form->Label('Category', 'CategoryID'), ' ';
         echo $this->Form->CategoryDropDown();
         echo '</div></p>';
      ?>
   </li>
   <?php } ?>
</ul>
<?php
echo $this->Form->Close('Create New Discussion');
