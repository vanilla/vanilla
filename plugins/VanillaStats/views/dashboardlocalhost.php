<?php if (!defined('APPLICATION')) exit(); ?>

<div class="Help Aside">
   <?php
   echo '<h2>', T('Need More Help?'), '</h2>';
   echo '<ul>';
   echo '<li>', Anchor(T('Using Vanilla Stats on localhost'), 'http://docs.vanillaforums.com/addons/statistics/'), '</li>';
   echo '</ul>';
   ?>
</div>
<div class="Messages Errors">
   <ul>
      <li><?php echo T('Vanilla statistics are disabled on localhost.'); ?></li>
   </ul>
</div>
