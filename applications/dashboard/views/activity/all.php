<?php if (!defined('APPLICATION')) exit(); ?>
<div class="ActivityFormWrap">
<h1 class="H"><?php echo $this->Data('Title'); ?></h1>
<?php
include_once $this->FetchViewLocation('helper_functions');

$this->FireEvent('BeforeStatusForm');
$Session = Gdn::Session();
if ($Session->CheckPermission('Garden.Profiles.Edit')) {
   echo $this->Form->Open(array('action' => Url('/activity/post/'.$this->Data('Filter')), 'class' => 'Activity'));
   echo $this->Form->Errors();
   echo Wrap($this->Form->TextBox('Comment', array('MultiLine' => TRUE)), 'div', array('class' => 'TextBoxWrapper'));
   echo $this->Form->Button('Share', array('class' => 'Button Primary'));
   echo $this->Form->Close();
}
echo '</div>';
echo '<ul class="DataList Activities">';

$Activities = $this->Data('Activities', array());
if (count($Activities) > 0) {
   include($this->FetchViewLocation('activities', 'activity', 'dashboard'));
   PagerModule::Write(array('CurrentRecords' => count($Activities)));
} else {
   ?>
<li><div class="Empty"><?php echo T('Not much happening here, yet.'); ?></div></li>
   <?php
}

echo '</ul>';