<?php if (!defined('APPLICATION')) exit(); ?>
    <div class="ActivityFormWrap">
    <h1 class="H"><?php echo $this->data('Title'); ?></h1>
<?php
include_once $this->fetchViewLocation('helper_functions');

$this->fireEvent('BeforeStatusForm');
$Session = Gdn::session();
if ($Session->checkPermission('Garden.Profiles.Edit')) {
    echo '<div class="FormWrapper FormWrapper-Condensed">';
    echo '<h2 class="sr-only">'.t('Post Comment').'</h2>';
    echo $this->Form->open(['action' => url('/activity/post/'.$this->data('Filter')), 'class' => 'Activity']);
    echo $this->Form->errors();
    echo $this->Form->bodyBox('Comment', ['Wrap' => true]);

    echo '<div class="Buttons">';
    echo $this->Form->button('Share', ['class' => 'Button Primary']);
    echo '</div>';

    echo $this->Form->close();
    echo '</div>';
}
echo '</div>';
echo '<h2 class="sr-only">'.t('Activity List').'</h2>';
echo '<ul class="DataList Activities">';

$Activities = $this->data('Activities', []);
if (count($Activities) > 0) {
    include($this->fetchViewLocation('activities', 'activity', 'dashboard'));
} else {
    ?>
<li><div class="Empty"><?php echo t('Not much happening here, yet.'); ?></div></li>
   <?php
}

echo '</ul>';

if (count($Activities) > 0)
    PagerModule::write(['CurrentRecords' => count($Activities)]);
