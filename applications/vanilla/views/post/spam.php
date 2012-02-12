<?php if (!defined('APPLICATION')) exit();
   echo $this->Form->Open();
   echo $this->Form->Errors();
   echo $this->Form->Close();
?>
<div class="Info">
<?php
echo '<div>', T("Your post will appear once it's been approved."), '</div>';

if ($this->Data('DiscussionUrl'))
   echo '<div>', sprintf(T('Click <a href="%s">here</a> to go back to the discussion.'), Url($this->Data('DiscussionUrl'))), '</div>';
?>
</div>