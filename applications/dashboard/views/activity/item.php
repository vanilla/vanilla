<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Tabs ActivityTabs">
   <ul>
      <li class="Active"><?php echo Anchor(T('Activity Item'), 'activity'); ?></li>
   </ul>
</div>
<?php
if ($this->ActivityData->NumRows() > 0) {
   echo '<ul class="DataList Activities">';
   include($this->FetchViewLocation('activities', 'activity', 'dashboard'));
   echo '</ul>';
} else {
   ?>
<div class="Empty"><?php echo T('Activity item not found.'); ?></div>
   <?php
}
