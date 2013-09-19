<?php if (!defined('APPLICATION')) exit();

echo '<h2 class="H">'.T('Activity').'</h2>';

$Session = Gdn::Session();
if ($Session->IsValid() && CheckPermission('Garden.Profiles.Edit')) {
   $this->FireEvent('BeforeStatusForm');      
   $ButtonText = $Session->UserID == $this->User->UserID ? 'Share' : 'Add Comment';
   
   
   echo '<div class="FormWrapper FormWrapper-Condensed">';
   echo $this->Form->Open(array('action' => Url("/activity/post/{$this->User->UserID}?Target=".urlencode(UserUrl($this->User))), 'class' => 'Activity'));
   echo $this->Form->Errors();
   echo Wrap($this->Form->BodyBox('Comment'), 'div', array('class' => 'TextBoxWrapper'));
   echo '<div class="Buttons">';
   echo $this->Form->Button($ButtonText, array('class' => 'Button Primary'));
   echo '</div>';
   echo $this->Form->Close();
   echo '</div>';
}

// Include the activities
include($this->FetchViewLocation('index', 'activity', 'dashboard'));
