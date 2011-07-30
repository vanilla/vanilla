<?php if (!defined('APPLICATION')) exit(); ?>

<div class="SplashInfo">
   <?php
   if ($this->Data('Success')) {
      echo '<h1>', T('Alive'), '</h1>';
      echo '<p>', T('Everything is ok.'), '</p>';
   }
   ?>
<!--   <p><?php echo T('The page you were looking for could not be found.'); ?></p>-->
</div>