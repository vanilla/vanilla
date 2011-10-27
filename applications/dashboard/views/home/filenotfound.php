<?php if (!defined('APPLICATION')) exit(); ?>

<div class="SplashInfo">
   <h1><?php echo $this->Data('Message', T('Page Not Found')); ?></h1>
   <p><?php echo T('The page you were looking for could not be found.'); ?></p>

<!-- Domain: <?php echo Gdn::Config('Garden.Domain', ''); ?> -->
<?php
if (Debug()) {
   if ($this->Data('ViewPaths')) {
      decho($this->Data('ViewPaths'), 'View Paths', TRUE);
   }
}
?>
</div>