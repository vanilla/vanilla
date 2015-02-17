<?php if (!defined('APPLICATION')) exit(); ?>
   <h1><?php echo $this->Data('Title'); ?></h1>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>
   <ul>
      <li>
         <?php
         echo $this->Form->Label('Type', 'FormType');
         echo $this->Form->Dropdown('FormType', $this->Data('FormTypes'));
         ?>
      </li>
      <li class="Label<?php if ($this->Form->GetValue('FormType') == 'DateOfBirth') echo ' Hidden'; ?>">
         <?php
         echo $this->Form->Label('Label', 'Label');
         echo $this->Form->TextBox('Label');
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
      <li class="ShowOnProfiles">
         <?php echo $this->Form->CheckBox('OnProfile', 'Show on profiles'); ?>
      </li>
      <!--<li>
         <?php echo $this->Form->CheckBox('OnDiscussion', 'Show on discussions'); ?>
      </li>-->
   </ul>
   <script>
      jQuery(document).ready(function($) {
         SetProfileFormOptions($);
      });
      $("select[name='FormType']").change(function() {
         SetProfileFormOptions($);
      });

      // Set options based on field type selected.
      function SetProfileFormOptions($) {
         $("[name='Required']").prop('disabled', false);
         $("[name='OnRegister']").prop('disabled', false);
         $("[name='OnProfile']").prop('disabled', false);
         switch ($("select[name='FormType']").val()) {
            case 'Dropdown':
               $('.Label').slideDown('fast');
               $('.Options').slideDown('fast');
               $('.ShowOnProfiles').slideDown('fast');
               break;
            case 'DateOfBirth':
               $('.Label').slideUp('fast');
               $('.Options').slideUp('fast');
               $('.ShowOnProfiles').slideDown('fast');
               break;
            case 'CheckBox':
               $('.Label').slideDown('fast');
               $('.Options').slideUp('fast');
               $('.ShowOnProfiles').slideUp('fast');
               $("[name='Required']").prop('checked', true);
               $("[name='Required']").prop('disabled', true);
               $("[name='OnRegister']").prop('checked', true);
               $("[name='OnRegister']").prop('disabled', true);
               $("[name='OnProfile']").prop('checked', false);
               $("[name='OnProfile']").prop('disabled', true);
               break;
            default:
               $('.Label').slideDown('fast');
               $('.Options').slideUp('fast');
               $('.ShowOnProfiles').slideDown('fast');
               break;
         }
      }
   </script>
<?php echo $this->Form->Close('Save');