<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo T($this->Data['Title']); ?></h1>
<div class="Info">
   <?php echo T("Voting allows users to vote on discussions & comments, giving a community score. Popular comments then rise to the top of the discussion."); ?>
</div>
<div class="FilterMenu">
      <?php
      echo Anchor(
         T(C('Plugins.Voting.Enabled') ? 'Disable Voting' : 'Enable Voting'),
         'settings/togglevoting/'.Gdn::Session()->TransientKey(),
         'SmallButton'
      );
   ?>
</div>