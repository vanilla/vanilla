<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo $this->Data('Title'); ?></h1>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();

echo Wrap(T('Choose the main discussion into which all comments will be merged:'), 'p');
$DiscussionData = GetValue('DiscussionData', $this->Data);
if (is_object($DiscussionData)) {
   $DefaultDiscussionID = $DiscussionData->FirstRow()->DiscussionID;
   echo '<ul><li>';
   echo $this->Form->RadioList('MergeDiscussionID', $DiscussionData, array('ValueField' => 'DiscussionID', 'TextField' => 'Name', 'Default' => $DefaultDiscussionID));
   echo '</li></ul>';
}
echo $this->Form->Close('Merge Discussions');
