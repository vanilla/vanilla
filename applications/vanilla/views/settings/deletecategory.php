<?php if (!defined('APPLICATION')) exit();

echo $this->Form->Open();
echo $this->Form->Errors();
if (is_object($this->OtherCategories)) {
   ?>
   <h1><?php echo T('Delete Category'); ?></h1>
   <ul>
   <?php
   if ($this->OtherCategories->NumRows() == 0) {
      ?>
      <li><p class="Warning"><?php echo T('Are you sure you want to delete this category?'); ?></p></li>
      <?php
   } else {
      // Only show the delete discussions checkbox if we're deleting a non-parent category.
      if ($this->Category->AllowDiscussions == '1') {
   ?>
      <li>
         <?php
            echo $this->Form->CheckBox('DeleteDiscussions', "Move discussions in this category to a replacement category.", array('value' => '1'));
         ?>
      </li>
   <?php }
   if ($this->Category->AllowDiscussions == '1') {
      ?>
      <li id="ReplacementWarning"><p class="Warning"><?php echo T('<strong>Heads Up!</strong> Moving discussions into a replacement category can result in discussions vanishing (or appearing) if the replacement category has different permissions than the category being deleted.'); ?></p></li>
      <?php
   }
   ?>
      <li id="ReplacementCategory">
         <?php
            echo $this->Form->Label('Replacement Category', 'ReplacementCategoryID');
            echo $this->Form->DropDown(
               'ReplacementCategoryID',
               $this->OtherCategories,
               array(
                  'ValueField' => 'CategoryID',
                  'TextField' => 'Name',
                  'IncludeNull' => TRUE
               ));
         ?>
      </li>
      <li id="DeleteDiscussions">
         <p class="Warning"><?php echo T('All discussions in this category will be permanently deleted.'); ?></p>
      </li>
   </ul>
   <?php
   }
   echo $this->Form->Close('Proceed');
}