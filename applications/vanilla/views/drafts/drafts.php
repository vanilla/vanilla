<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
$ShowOptions = TRUE;
$Alt = '';
foreach ($this->DraftData->Result() as $Draft) {
   $EditUrl = !is_numeric($Draft->DiscussionID) || $Draft->DiscussionID <= 0 ? '/post/editdiscussion/0/'.$Draft->DraftID : '/post/editcomment/0/'.$Draft->DraftID;
   $Alt = $Alt == ' Alt' ? '' : ' Alt';
   ?>
   <li class="Item Draft<?php echo $Alt; ?>">
      <div class="OptionButton"><?php echo Anchor(T('Delete'), 'vanilla/drafts/delete/'.$Draft->DraftID.'/'.$Session->TransientKey().'?Target='.urlencode($this->SelfUrl), 'Delete'); ?></div>
      <div class="ItemContent">
         <?php echo Anchor($Draft->Name, $EditUrl, 'Title DraftLink'); ?>
         <div class="Excerpt"><?php
            echo Anchor(SliceString(Gdn_Format::Text($Draft->Body), 200), $EditUrl);
         ?></div>
      </div>
   </li>
   <?php
}