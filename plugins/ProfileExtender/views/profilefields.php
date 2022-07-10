<?php if (!defined('APPLICATION')) exit();

/** @var ProfileController $Sender */
if (is_array($this->ProfileFields)) {
    foreach ($this->ProfileFields as $k => $Field) {
        $Name = isset($Field['Name']) ? $Field['Name'] : $k;
        $Options = [];
        if ($Field['FormType'] == 'Dropdown') {
            $values = $Field['Options'];
            $labels = val('OptionsLabels', $Field, $Field['Options']);

            // If the config provides an array of labels nested in the profile field ($Field['OptionsLabels']),
            // combine the arrays to create a drop-down with different values and labels.
            $Options = array_combine($values, $labels);
            $dropdownElements = array_combine($values, $labels);
            if ($dropdownElements !== false) {
                $Options = $dropdownElements;
            } else {
                $Options = [null => t('Missing Dropdown. Number of labels does not match number of elements.')];
            }
        } elseif ($Field['FormType'] == 'CheckBox') {
            $Options = [];
        } else {
            $Options = (isset($Field['Options']) && is_array($Field['Options']) ? $Field['Options'] : []);
        }

        $formInput = "";
        if ($Field['FormType'] === 'CheckBox') {
            $formInput = $Sender->Form->checkBox($Name, $Field['Label']);
        } else {
            $formInput = '<div class="label-wrap">'.$Sender->Form->label($Field['Label'], $Name).'</div>'.
                '<div class="input-wrap input-wrap-multiple">'.$Sender->Form->{$Field['FormType']}($Name, $Options).'</div>';
        }

        echo wrap($formInput, 'li', ['class' => 'form-group']);
    }
}
