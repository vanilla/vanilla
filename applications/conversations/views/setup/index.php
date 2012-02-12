<?php if (!defined('APPLICATION')) exit();
echo '<h2>' . T('Conversations Setup') . '</h2>';
echo $this->Form->Open();
echo $this->Form->Errors();
echo $this->Form->Close('Continue');