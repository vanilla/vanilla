<?php if (!defined('APPLICATION')) exit();
	
$CountFields = 0;
foreach ($Sender->RegistrationFields as $Field) {
	$CountFields++;
	echo '<li>';
		echo $Sender->Form->Hidden('CustomLabel[]', array('value' => $Field));
		echo $Sender->Form->Label($Field, 'CustomValue[]');
		echo $Sender->Form->TextBox('CustomValue[]');
	echo '</li>';
}