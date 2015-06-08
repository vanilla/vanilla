<?php if (!defined('APPLICATION')) exit();
echo '<h2>'.t('Conversations Setup').'</h2>';
echo $this->Form->open();
echo $this->Form->errors();
echo $this->Form->close('Continue');
