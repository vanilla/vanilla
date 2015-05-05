<?php if (!defined('APPLICATION')) exit(); ?>
<h2 class="H self-clearing"><?php echo $this->Data('Title'); ?></h2>
<?php
echo $this->Form->Open(array('class' => 'ten columns'));
echo $this->Form->Errors();
?>
<ul class="self-clearing">
   <li class="Gender User-Gender">
      <?php
         echo $this->Form->Label('Gender', 'Gender');
         echo $this->Form->RadioList('Gender', $this->GenderOptions, array('default' => 'u'))
      ?>
   </li>
   
   <?php if (C('Garden.Profile.Titles', FALSE)): ?>
   <li class="User-Title">
      <?php
         echo $this->Form->Label('Title', 'Title');
         echo $this->Form->TextBox('Title');
      ?>
   </li>
   <?php endif; ?>
   
   <?php if (C('Garden.Profile.Locations', FALSE)): ?>
   <li class="User-Location">
      <?php
         echo $this->Form->Label('Location', 'Location');
         echo $this->Form->TextBox('Location');
      ?>
   </li>
   <?php endif; ?>
   
   <?php
      $this->FireEvent('EditMyAccountAfter');
   ?>
</ul><br>
<?php echo $this->Form->Close('Save', '', array('class' => 'button Button Primary self-clearing'));
