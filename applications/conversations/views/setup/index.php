<?php if (!defined('APPLICATION')) exit();
echo '<h2>Conversations Setup</h2>';
echo $this->Form->Open();
echo $this->Form->Errors();
echo $this->Form->Close('Continue');