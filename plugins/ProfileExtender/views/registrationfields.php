<?php if (!defined('APPLICATION')) exit();

if (is_array($Sender->RegistrationFields)) {
   foreach ($Sender->RegistrationFields as $Name => $Field) {
      $Options = array();
      if ($Field['FormType'] == 'Dropdown')
         $Options = array_combine($Field['Options'], $Field['Options']);

      if ($Field['FormType'] == 'CheckBox') {
         echo Wrap($Sender->Form->{$Field['FormType']}($Name, $Field['Label']), 'li');
      }
      else {
         echo Wrap($Sender->Form->Label($Field['Label'], $Name) .
            $Sender->Form->{$Field['FormType']}($Name, $Options), 'li');
      }
   }
}