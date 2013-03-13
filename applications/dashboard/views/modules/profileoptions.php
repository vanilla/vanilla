<?php if (!defined('APPLICATION')) exit();
/** Displays the "Edit My Profile" or "Back to Profile" buttons on the top of the profile page. */
?>
<div class="ProfileOptions">
   <?php
   $Controller = Gdn::Controller();
   $Controller->FireEvent('BeforeProfileOptions');
   echo ButtonGroup($Controller->EventArguments['MemberOptions'], 'NavButton MemberButtons');
   echo ' ';
   echo ButtonDropDown($Controller->EventArguments['ProfileOptions'], 
      'NavButton ProfileButtons Button-EditProfile',
      Sprite('SpEditProfile', 'Sprite16').' <span class="Hidden">'.T('Edit Profile').'</span>'
   );
   ?>
</div>
