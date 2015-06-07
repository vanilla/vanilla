<?php if (!defined('APPLICATION')) exit();
echo '<ul class="DataList Activities">';
if (count($this->data('Activities')) > 0) {
    include($this->fetchViewLocation('activities', 'activity', 'dashboard'));
} else {
    ?>
    <li>
        <div class="Empty"><?php echo t('Not much happening here, yet.'); ?></div>
    </li>
<?php
}
echo '</ul>';

if (count($this->data('Activities')) > 0)
    PagerModule::write(array('CurrentRecords' => count($this->data('Activities'))));
