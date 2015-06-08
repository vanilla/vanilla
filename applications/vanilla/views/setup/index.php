<?php if (!defined('APPLICATION')) exit();
echo '<h2>Vanilla Setup</h2>';
echo $this->Form->open();
echo $this->Form->errors();
echo $this->Form->Input('Vanilla.Test');
echo $this->Form->close('Continue');
