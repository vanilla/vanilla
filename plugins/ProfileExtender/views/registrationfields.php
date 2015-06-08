<?php if (!defined('APPLICATION')) exit();

if (is_array($Sender->RegistrationFields)) {
    foreach ($Sender->RegistrationFields as $Name => $Field) {
        $Options = array();
        if ($Field['FormType'] == 'Dropdown')
            $Options = array_combine($Field['Options'], $Field['Options']);

        if ($Field['FormType'] == 'CheckBox') {
            echo wrap($Sender->Form->{$Field['FormType']}($Name, $Field['Label']), 'li');
        } else {
            echo wrap($Sender->Form->label($Field['Label'], $Name).
                $Sender->Form->{$Field['FormType']}($Name, $Options), 'li');
        }
    }
}
