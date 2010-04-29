<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
if ($Session->IsValid()) {
   $ButtonText = $Session->UserID == $this->User->UserID ? 'Share' : 'Add Comment';
   echo $this->Form->Open(array('action' => Url('/profile/'.$this->User->UserID.'/'.$this->User->Name), 'class' => 'Activity'));
   echo $this->Form->Errors();
   echo $this->Form->TextBox('Comment', array('MultiLine' => TRUE));
   echo $this->Form->Button($ButtonText);
   echo $this->Form->Close();
}

// Include the activities
include($this->FetchViewLocation('index', 'activity', 'dashboard'));