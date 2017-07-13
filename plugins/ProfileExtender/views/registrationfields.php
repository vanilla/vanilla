<?php if (!defined('APPLICATION')) exit();

if (is_array($Sender->RegistrationFields)) {
    foreach ($Sender->RegistrationFields as $Name => $Field) {
        $Options = [];
        if ($Field['FormType'] == 'Dropdown') {
            $values = $Field['Options'];
            $labels = val('OptionsLabels', $Field, $Field['Options']);

            // If the config provides an array of labels nested in the profile field ($Field['OptionsLabels']),
            // combine the arrays to create a drop-down with different values and labels.
            $Options = array_combine($values, $labels);
        }

        if ($Field['FormType'] == 'CheckBox') {
            echo wrap($Sender->Form->{$Field['FormType']}($Name, $Field['Label']), 'li');
        } else {
            echo wrap($Sender->Form->label($Field['Label'], $Name).
                $Sender->Form->{$Field['FormType']}($Name, $Options), 'li', ['class' => 'form-group']);
        }
    }
}
