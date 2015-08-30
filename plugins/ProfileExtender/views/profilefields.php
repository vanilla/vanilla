<?php if (!defined('APPLICATION')) exit();

if (is_array($this->ProfileFields)) {
    foreach ($this->ProfileFields as $Name => $Field) {
        $Options = array();
        if ($Field['FormType'] == 'Dropdown')
            $Options = array_combine($Field['Options'], $Field['Options']);

        if ($Field['FormType'] == 'TextBox' && !empty($Field['Options'])) {
            $Options = $Field['Options'];
        }

        if ($Field['FormType'] == 'CheckBox') {
            continue;
        } else {
            echo wrap($Sender->Form->label($Field['Label'], $Name).
                $Sender->Form->{$Field['FormType']}($Name, $Options), 'li');
        }
    }
}
