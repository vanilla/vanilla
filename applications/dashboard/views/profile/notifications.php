<?php if (!defined('APPLICATION')) exit();

echo '<div class="DataListWrap">';
echo '<h1 class="H">'.t('Notifications').'</h1>';

if (count($this->data('Activities'))) {
    echo '<ul class="DataList Activities Notifications">';
    include($this->fetchViewLocation('activities', 'activity', 'dashboard'));
    echo '</ul>';
    echo PagerModule::write(['CurrentRecords' => count($this->data('Activities'))]);
} else {
    ?>
    <div class="Empty"><?php echo t('Notifications will appear here.', t('You do not have any notifications yet.')); ?></div>
<?php
}
echo '</div>';
