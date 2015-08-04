<?php if (!defined('APPLICATION')) exit(); ?>
    <div class="Tabs ActivityTabs">
        <ul>
            <li class="Active"><?php echo anchor(t('Activity Item'), 'activity'); ?></li>
        </ul>
    </div>
<?php
if ($this->ActivityData->numRows() > 0) {
    echo '<ul class="DataList Activities">';
    include($this->fetchViewLocation('activities', 'activity', 'dashboard'));
    echo '</ul>';
} else {
    ?>
    <div class="Empty"><?php echo t('Activity item not found.'); ?></div>
<?php
}
