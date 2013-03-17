<?php if (!defined('APPLICATION')) exit();

echo '<h2 class="H">'.T('Activity').'</h2>';

$Session = Gdn::Session();
if ($Session->IsValid() && CheckPermission('Garden.Profiles.Edit')) {
   $this->FireEvent('BeforeStatusForm');      
   $ButtonText = $Session->UserID == $this->User->UserID ? 'Share' : 'Add Comment';
   echo $this->Form->Open(array('action' => Url("/activity/post/{$this->User->UserID}?Target=".urlencode(UserUrl($this->User))), 'class' => 'Activity'));
   echo $this->Form->Errors();
   echo Wrap($this->Form->BodyBox('Comment'), 'div', array('class' => 'TextBoxWrapper'));
   echo $this->Form->Button($ButtonText, array('class' => 'Button Primary'));
   echo $this->Form->Close();
}

// Include the activities
include($this->FetchViewLocation('index', 'activity', 'dashboard'));