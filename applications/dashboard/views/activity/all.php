<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo $this->Data('Title'); ?></h1>
<?php
include_once $this->FetchViewLocation('helper_functions');

// WriteActivityTabs();
// echo Gdn_Theme::Module('ActivityFilterModule');

$this->FireEvent('BeforeStatusForm');
$Session = Gdn::Session();
if ($Session->CheckPermission('Garden.Profiles.Edit')) {
   echo $this->Form->Open(array('action' => Url('/activity/post/'.$this->Data('Filter')), 'class' => 'Activity'));
   echo $this->Form->Errors();
   echo Wrap($this->Form->TextBox('Comment', array('MultiLine' => TRUE)), 'div', array('class' => 'TextBoxWrapper'));
   echo $this->Form->Button(T('Share'));
   echo $this->Form->Close();
}
echo '<ul class="DataList Activities">';

$Activities = $this->Data('Activities', array());
if (count($Activities) > 0) {
   include($this->FetchViewLocation('activities', 'activity', 'dashboard'));
   
   echo '<div class="P">';
   PagerModule::Write(array('CurrentRecords' => count($Activities)));
   echo '</div>';
} else {
   ?>
<li><div class="Empty"><?php echo T('Not much happening here, yet.'); ?></div></li>
   <?php
}

echo '</ul>';