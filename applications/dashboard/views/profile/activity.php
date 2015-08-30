<?php if (!defined('APPLICATION')) exit();

echo '<div class="DataListWrap">';
echo '<h2 class="H">'.t('Activity').'</h2>';

$Session = Gdn::session();
if ($Session->isValid() && checkPermission('Garden.Profiles.Edit')) {
    $this->fireEvent('BeforeStatusForm');
    $ButtonText = $Session->UserID == $this->User->UserID ? 'Share' : 'Add Comment';


    echo '<div class="FormWrapper FormWrapper-Condensed">';
    echo $this->Form->open(array('action' => url("/activity/post/{$this->User->UserID}?Target=".urlencode(userUrl($this->User))), 'class' => 'Activity'));
    echo $this->Form->errors();
    echo $this->Form->bodyBox('Comment', array('Wrap' => TRUE));
    echo '<div class="Buttons">';
    echo $this->Form->button($ButtonText, array('class' => 'Button Primary'));
    echo '</div>';
    echo $this->Form->close();
    echo '</div>';
}

// Include the activities
include($this->fetchViewLocation('index', 'activity', 'dashboard'));
echo '</div>';
