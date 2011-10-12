<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo $this->Data('Title'); ?></h1>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();

$Discussions = $this->Data('Discussions');
if (count($Discussions) < 2) {
   echo Wrap(T('You have to select at least 2 discussions to merge.'), 'p');
} else {
   echo Wrap(T('Choose the main discussion into which all comments will be merged:'), 'p');

   $DefaultDiscussionID = $Discussions[0]['DiscussionID'];
   $RadioData = ConsolidateArrayValuesByKey($Discussions, 'DiscussionID', 'Name');
   array_map('htmlspecialchars', $RadioData);
   echo '<ul><li>';
   echo $this->Form->RadioList('MergeDiscussionID', $RadioData, array('ValueField' => 'DiscussionID', 'TextField' => 'Name', 'Default' => $DefaultDiscussionID));
   echo '</li></ul>';
   echo $this->Form->Button('Merge Discussions');
}
echo $this->Form->Close();
