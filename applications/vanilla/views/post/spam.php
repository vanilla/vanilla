<?php if (!defined('APPLICATION')) exit();
   echo $this->Form->Open();
   echo $this->Form->Errors();
   echo $this->Form->Close();
?>
<div class="Info">
<?php
if ($this->RequestMethod == 'discussion')
	$Message = T('DiscussionRequiresApproval', "Your discussion will appear after it is approved.");
else
	$Message = T('CommentRequiresApproval', "Your comment will appear after it is approved.");
echo '<div>', $Message, '</div>';

if ($this->Data('DiscussionUrl'))
   echo '<div>', sprintf(T('Click <a href="%s">here</a> to go back to the discussion.'), Url($this->Data('DiscussionUrl'))), '</div>';
else
   echo '<div>', Anchor('Back to the discussions list.', 'discussions'), '</div>';
?>
</div>