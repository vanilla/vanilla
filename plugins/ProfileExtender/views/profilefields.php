<?php if (!defined('APPLICATION')) exit();

// Get posted values
$CustomLabels = GetValue('CustomLabel', $Sender->Form->FormValues(), array());
$CustomValues = GetValue('CustomValue', $Sender->Form->FormValues(), array());

// Write out the suggested fields first
if (count($this->ProfileFields) > 0)
   echo Wrap(Wrap(T('More Information'), 'label'), 'li');
	
foreach ($this->ProfileFields as $Name => $Field) {
	echo '<li>';
		echo $Sender->Form->Label($Field['Label'], $Name);

      $Options = array();
      if ($Field['FormType'] == 'Dropdown') {
         $Options =  array_combine($Field['Options'], $Field['Options']);
      }
		echo $Sender->Form->{$Field['FormType']}($Name, $Options);
	echo '</li>';
}