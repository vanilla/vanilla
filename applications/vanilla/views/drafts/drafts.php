<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
$ShowOptions = TRUE;
$Alt = '';
foreach ($this->DraftData->Result() as $Draft) {
	$Offset = GetValue('CountComments', $Draft, 0);
	if($Offset > C('Vanilla.Comments.PerPage', 30)) {
		$Offset -= C('Vanilla.Comments.PerPage', 30);
	} else {
		$Offset = 0;
	}
	
   $EditUrl = !is_numeric($Draft->DiscussionID) || $Draft->DiscussionID <= 0 ? '/post/editdiscussion/0/'.$Draft->DraftID : '/discussion/'.$Draft->DiscussionID.'/'.$Offset.'/#Form_Comment';
   $Alt = $Alt == ' Alt' ? '' : ' Alt';
   ?>
   <li class="Item Draft<?php echo $Alt; ?>">
      <div class="Options"><?php echo Anchor(T('Draft.Delete', 'Delete'), 'vanilla/drafts/delete/'.$Draft->DraftID.'/'.$Session->TransientKey().'?Target='.urlencode($this->SelfUrl), 'Delete'); ?></div>
      <div class="ItemContent">
         <?php echo Anchor(Gdn_Format::Text($Draft->Name, FALSE), $EditUrl, 'Title DraftLink'); ?>
         <div class="Excerpt"><?php
            echo Anchor(SliceString(Gdn_Format::Text($Draft->Body), 200), $EditUrl);
         ?></div>
      </div>
   </li>
   <?php
}