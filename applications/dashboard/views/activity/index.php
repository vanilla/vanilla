<?php use Vanilla\Theme\BoxThemeShim;

if (!defined('APPLICATION')) exit();

if (!$this->data('activityBoxIsSet')) {
    BoxThemeShim::startBox();
}
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
if (!$this->data('activityBoxIsSet')) {
    BoxThemeShim::endBox();
}

if (count($this->data('Activities')) > 0) {
    PagerModule::write(['CurrentRecords' => count($this->data('Activities'))]);
}
