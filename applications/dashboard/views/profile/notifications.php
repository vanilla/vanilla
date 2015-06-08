<?php if (!defined('APPLICATION')) exit();

echo '<div class="DataListWrap">';
echo '<h2 class="H">'.t('Notifications').'</h2>';

if (count($this->data('Activities'))) {
    echo '<ul class="DataList Activities Notifications">';
    include($this->fetchViewLocation('activities', 'activity', 'dashboard'));
    echo '</ul>';
    echo PagerModule::write(array('CurrentRecords' => count($this->data('Activities'))));
} else {
    ?>
    <div class="Empty"><?php echo t('You do not have any notifications yet.'); ?></div>
<?php
}
echo '</div>';
