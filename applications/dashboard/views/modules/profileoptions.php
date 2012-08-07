<?php if (!defined('APPLICATION')) exit();
/** Displays the "Edit My Profile" or "Back to Profile" buttons on the top of the profile page. */
?>
<div class="ProfileOptions">
   <?php
   $Controller = Gdn::Controller();
   $Controller->FireEvent('BeforeProfileOptions');
   echo ButtonGroup($Controller->EventArguments['MemberOptions'], 'NavButton MemberButtons');
   echo ' ';
   echo ButtonGroup($Controller->EventArguments['ProfileOptions'], 'NavButton ProfileButtons');
   ?>
</div>
