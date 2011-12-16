<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Tabs ActivityTabs">
   <ul>
      <li class="Active"><?php echo Anchor(T('Recent Activity'), 'activity', 'TabLink'); ?></li>
   </ul>
</div>
<?php
$this->FireEvent('BeforeStatusForm');
$Session = Gdn::Session();
if ($Session->CheckPermission('Garden.Profiles.Edit')) {
   echo $this->Form->Open(array('action' => Url('/activity/post'), 'class' => 'Activity'));
   echo $this->Form->Errors();
   echo Wrap($this->Form->TextBox('Comment', array('MultiLine' => TRUE)), 'div', array('class' => 'TextBoxWrapper'));
   echo $this->Form->Button(T('Share'));
   echo $this->Form->Close();
}
echo '<ul class="DataList Activities">';

$Activities = $this->Data('Activities', array());
if (count($Activities) > 0) {
   include($this->FetchViewLocation('activities', 'activity', 'dashboard'));
   echo PagerModule::Write(array('CurrentRecords' => count($Activities)));
} else {
   ?>
<li><div class="Empty"><?php echo T('Not much happening here, yet.'); ?></div></li>
   <?php
}

echo '</ul>';