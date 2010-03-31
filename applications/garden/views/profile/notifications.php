<?php if (!defined('APPLICATION')) exit();
if ($this->ActivityData->NumRows() > 0) {
   echo '<ul class="Activities Notifications">';
   include($this->FetchViewLocation('activities', 'activity', 'garden'));
   echo '</ul>';
} else {
   ?>
<div class="Info EmptyInfo"><?php echo T('You do not have any notifications yet.'); ?></div>
   <?php
}