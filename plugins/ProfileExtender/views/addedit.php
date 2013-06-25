<?php if (!defined('APPLICATION')) exit(); ?>
   <h1><?php echo $this->Data('Title'); ?></h1>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>
   <ul>
      <li>
         <?php
         echo $this->Form->Label('Label', 'Label');
         echo $this->Form->TextBox('Label');
         ?>
      </li>
      <li>
         <?php
         echo $this->Form->Label('Type', 'FormType');
         echo $this->Form->Dropdown('FormType', $this->Data('FormTypes'));
         ?>
      </li>
      <li class="Options<?php if ($this->Form->GetValue('FormType') != 'Dropdown') echo ' Hidden'; ?>">
         <?php

         echo $this->Form->Label('Options', 'Options');
         echo Wrap(T('One option per line'), 'p');
         echo $this->Form->TextBox('Options', array('MultiLine' => TRUE));
         ?>
      </li>
      <li>
         <?php echo $this->Form->CheckBox('Required', 'Required for all users'); ?>
      </li>
      <li>
         <?php echo $this->Form->CheckBox('OnRegister', 'Show on registration'); ?>
      </li>
      <li>
         <?php echo $this->Form->CheckBox('OnProfile', 'Show on profiles'); ?>
      </li>
      <!--<li>
         <?php echo $this->Form->CheckBox('OnDiscussion', 'Show on discussions'); ?>
      </li>-->
   </ul>
   <script>
      $("select[name='FormType']").change(function() {
         if ($("select[name='FormType']").val() == 'Dropdown')
            $('.Options').slideDown('fast');
         else
            $('.Options').slideUp('fast');
      });
   </script>
<?php echo $this->Form->Close('Save');