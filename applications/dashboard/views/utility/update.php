<?php if (!defined('APPLICATION')) exit(); ?>

<div class="SplashInfo">
   <?php
   if ($this->Data('Success')) {
      echo '<h1>', T('Success'), '</h1>';
      echo '<p>', T('The update was successful.'), '</p>';
   }
   ?>
<!--   <p><?php echo T('The page you were looking for could not be found.'); ?></p>-->
</div>