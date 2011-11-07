<?php if (!defined('APPLICATION')) exit();
	
$CountFields = 0;
foreach ($this->RegistrationFields as $Field) {
	$CountFields++;
	echo '<li>';
		echo $this->Form->Hidden('CustomLabel[]', array('value' => $Field));
		echo $this->Form->Label($Field, 'CustomValue[]');
		echo $this->Form->TextBox('CustomValue[]');
	echo '</li>';
}