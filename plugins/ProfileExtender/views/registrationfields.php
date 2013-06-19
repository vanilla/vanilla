<?php if (!defined('APPLICATION')) exit();

if (is_array($Sender->RegistrationFields)) {
   foreach ($Sender->RegistrationFields as $Name => $Field) {
      $Options = array();
      if ($Field['FormType'] == 'Dropdown')
         $Options =  array_combine($Field['Options'], $Field['Options']);

      echo Wrap($Sender->Form->Label($Field['Label'], $Name).
         $Sender->Form->{$Field['FormType']}($Name, $Options), 'li');
   }
}