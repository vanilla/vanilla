<?php if (!defined('APPLICATION')) exit();
$User = val('User', $this->_Sender);
if ($User):
    echo '<div class="UserBox">';
    echo userPhoto($User);
    echo '<div class="WhoIs">';
    echo userAnchor($User, 'Username');
    echo '<div class="Email">';

    // Only show the email address if allowed.
    if (val('UserID', $User) == Gdn::session()->UserID || Gdn::session()->checkPermission('Garden.PersonalInfo.View')) {
        echo htmlspecialchars(val('Email', $User, ''));
    } else {
        echo '&nbsp;';
    }

    echo '</div>';
    echo '</div>';
    echo '</div>';
endif;
