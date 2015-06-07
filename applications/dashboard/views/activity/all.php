<?php if (!defined('APPLICATION')) exit(); ?>
    <div class="ActivityFormWrap">
    <h1 class="H"><?php echo $this->data('Title'); ?></h1>
<?php
include_once $this->fetchViewLocation('helper_functions');

$this->fireEvent('BeforeStatusForm');
$Session = Gdn::session();
if ($Session->checkPermission('Garden.Profiles.Edit')) {
    echo '<div class="FormWrapper FormWrapper-Condensed">';
    echo $this->Form->open(array('action' => url('/activity/post/'.$this->data('Filter')), 'class' => 'Activity'));
    echo $this->Form->errors();
    echo $this->Form->textBox('Comment', array('MultiLine' => true, 'Wrap' => TRUE));

    echo '<div class="Buttons">';
    echo $this->Form->button('Share', array('class' => 'Button Primary'));
    echo '</div>';

    echo $this->Form->close();
    echo '</div>';
}
echo '</div>';
echo '<ul class="DataList Activities">';

$Activities = $this->data('Activities', array());
if (count($Activities) > 0) {
    include($this->fetchViewLocation('activities', 'activity', 'dashboard'));
} else {
    ?>
<li><div class="Empty"><?php echo t('Not much happening here, yet.'); ?></div></li>
   <?php
}

echo '</ul>';

if (count($Activities) > 0)
    PagerModule::write(array('CurrentRecords' => count($Activities)));
