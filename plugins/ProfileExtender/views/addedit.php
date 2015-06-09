<?php if (!defined('APPLICATION')) exit(); ?>
    <h1><?php echo $this->data('Title'); ?></h1>
<?php
echo $this->Form->open();
echo $this->Form->errors();
?>
    <ul>
        <li>
            <?php
            echo $this->Form->label('Type', 'FormType');
            echo $this->Form->Dropdown('FormType', $this->data('FormTypes'));
            ?>
        </li>
        <li class="Label<?php if ($this->Form->getValue('FormType') == 'DateOfBirth') echo ' Hidden'; ?>">
            <?php
            echo $this->Form->label('Label', 'Label');
            echo $this->Form->textBox('Label');
            ?>
        </li>
        <li class="Options<?php if ($this->Form->getValue('FormType') != 'Dropdown') echo ' Hidden'; ?>">
            <?php

            echo $this->Form->label('Options', 'Options');
            echo wrap(t('One option per line'), 'p');
            echo $this->Form->textBox('Options', array('MultiLine' => TRUE));
            ?>
        </li>
        <li>
            <?php echo $this->Form->checkBox('Required', 'Required for all users'); ?>
        </li>
        <li>
            <?php echo $this->Form->checkBox('OnRegister', 'Show on registration'); ?>
        </li>
        <li class="ShowOnProfiles">
            <?php echo $this->Form->checkBox('OnProfile', 'Show on profiles'); ?>
        </li>
        <!--<li>
         <?php echo $this->Form->checkBox('OnDiscussion', 'Show on discussions'); ?>
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
<?php echo $this->Form->close('Save');
