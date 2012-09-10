<?php if (!defined('APPLICATION')) exit(); ?>

<div class="Center SplashInfo">
   <h1><?php echo $this->Data('Message', T('Page Not Found')); ?></h1>
   <div id="Message"><?php echo $this->Data('Description', T('The page you were looking for could not be found.')); ?></div>

<!-- Domain: <?php echo Gdn::Config('Garden.Domain', ''); ?> -->
</div>