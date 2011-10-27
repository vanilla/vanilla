<?php if (!defined('APPLICATION')) exit();
if ($this->ActivityData->NumRows() > 0) {
   echo '<ul class="DataList Activities">';
   include($this->FetchViewLocation('activities', 'activity', 'dashboard'));
   echo '</ul>';

   echo PagerModule::Write(array('CurrentRecords' => $this->ActivityData->NumRows()));
} else {
   ?>
<div class="Empty"><?php echo T('Not much happening here, yet.'); ?></div>
   <?php
}
