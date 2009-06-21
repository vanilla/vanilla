<?php if (!defined('APPLICATION')) exit();
echo $this->Form->Open(array('action' => Url('/profile/'.$this->User->Name), 'class' => 'Activity'));
echo $this->Form->Errors();
echo $this->Form->TextBox('Comment', array('MultiLine' => TRUE));
echo $this->Form->Button(Gdn::Translate('Add Comment'));
echo $this->Form->Close();

// Include the activities
include($this->FetchViewLocation('index', 'activity', 'garden'));