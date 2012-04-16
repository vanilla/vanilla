<?php if (!defined('APPLICATION')) exit();
echo '<h1 class="H HomepageTitle">'.$this->Data('Title').'</h1>';
include($this->FetchViewLocation('helper_functions', 'discussions', 'vanilla'));
$Session = Gdn::Session();
$ShowOptions = TRUE;
$Alt = '';
$ViewLocation = $this->FetchViewLocation('drafts', 'drafts');
// WriteFilterTabs($this);
echo Gdn_Theme::Module('DiscussionFilterModule');
if ($this->DraftData->NumRows() > 0) {
   echo $this->Pager->ToString('less');
?>
<ul class="DataList Drafts">
   <?php
   include($ViewLocation);
   ?>
</ul>
   <?php
   echo $this->Pager->ToString('more');
} else {
   ?>
   <div class="Empty"><?php echo T('You do not have any drafts.'); ?></div>
   <?php
}
