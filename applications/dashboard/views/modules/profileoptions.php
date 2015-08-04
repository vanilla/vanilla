<?php if (!defined('APPLICATION')) exit();
/** Displays the "Edit My Profile" or "Back to Profile" buttons on the top of the profile page. */
?>
<div class="ProfileOptions">
    <?php
    $Controller = Gdn::controller();
    $Controller->fireEvent('BeforeProfileOptions');
    echo ButtonGroup($Controller->EventArguments['MemberOptions'], 'NavButton MemberButtons');
    echo ' ';
    echo ButtonDropDown($Controller->EventArguments['ProfileOptions'],
        'NavButton ProfileButtons Button-EditProfile',
        sprite('SpEditProfile', 'Sprite16').' <span class="Hidden">'.t('Edit Profile').'</span>'
    );
    ?>
</div>
