<?php if (!defined('APPLICATION')) exit();
if (count($this->Data('Activities')) > 0) {
   echo '<ul class="DataList Activities">';
   include($this->FetchViewLocation('activities', 'activity', 'dashboard'));
   echo '</ul>';
   
   echo PagerModule::Write(array('CurrentRecords' => count($this->Data('Activities'))));
} else {
   ?>
<div class="Empty"><?php echo T('Not much happening here, yet.'); ?></div>
   <?php
}
