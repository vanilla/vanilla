<?php if (!defined('APPLICATION')) exit(); ?>

<div class="SplashInfo">
   <?php
   if ($this->Data('valid')) {
      echo '<h1>', T('Pong'), '</h1>';
   }
   ?>
</div>