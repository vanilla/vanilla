<?php if (!defined('APPLICATION')) exit();
echo '<ul class="DataList Activities">';
if (count($this->Data('Activities')) > 0) {
   include($this->FetchViewLocation('activities', 'activity', 'dashboard'));
} else {
   ?>
<li><div class="Empty"><?php echo T('Not much happening here, yet.'); ?></div></li>
   <?php
}
echo '</ul>';

if (count($this->Data('Activities')) > 0)
   PagerModule::Write(array('CurrentRecords' => count($this->Data('Activities'))));