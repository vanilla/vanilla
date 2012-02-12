<?php if (!defined('APPLICATION')) exit();
echo '<h2>Vanilla Setup</h2>';
echo $this->Form->Open();
echo $this->Form->Errors();
echo $this->Form->Input('Vanilla.Test');
echo $this->Form->Close('Continue');