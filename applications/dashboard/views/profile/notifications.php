<?php if (!defined('APPLICATION')) exit();
if ($this->ActivityData->NumRows() > 0) {
   echo '<ul class="DataList Activities Notifications">';
   include($this->FetchViewLocation('activities', 'activity', 'dashboard'));
   echo '</ul>';
} else {
   ?>
<div class="Empty"><?php echo T('You do not have any notifications yet.'); ?></div>
   <?php
}