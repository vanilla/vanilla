<?php if (!defined('APPLICATION')) exit();

if (is_array($this->ProfileFields)) {
    foreach ($this->ProfileFields as $Name => $Field) {
        $Options = [];
        if ($Field['FormType'] == 'Dropdown') {
            $values = $Field['Options'];
            $labels = val('OptionsLabels', $Field, $Field['Options']);

            // If the config provides an array of labels nested in the profile field ($Field['OptionsLabels']),
            // combine the arrays to create a drop-down with different values and labels.
            $Options = array_combine($values, $labels);
        }

        if ($Field['FormType'] == 'TextBox' && !empty($Field['Options'])) {
            $Options = $Field['Options'];
        }

        if ($Field['FormType'] == 'CheckBox') {
            continue;
        } else {
            echo wrap('<div class="label-wrap">'.$Sender->Form->label($Field['Label'], $Name).'</div>'.
                '<div class="input-wrap">'.$Sender->Form->{$Field['FormType']}($Name, $Options).'</div>', 'li', ['class' => 'form-group']);
        }
    }
}
