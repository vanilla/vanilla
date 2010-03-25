<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
$ShowOptions = TRUE;
$Alt = '';
foreach ($this->DraftData->Result() as $Draft) {
   $EditUrl = !is_numeric($Draft->DiscussionID) || $Draft->DiscussionID <= 0 ? '/post/editdiscussion/0/'.$Draft->DraftID : '/post/editcomment/0/'.$Draft->DraftID;
   $Alt = $Alt == ' Alt' ? '' : ' Alt';
   ?>
   <li class="<?php echo 'DiscussionRow Draft'.$Alt; ?>">
      <ul>
         <li class="Title">
            <?php
               echo Anchor(Gdn::Translate('Delete'), 'vanilla/drafts/delete/'.$Draft->DraftID.'/'.$Session->TransientKey().'?Target='.urlencode($this->SelfUrl), 'DeleteDraft');
            ?>
            <strong><?php
               echo Anchor($Draft->Name, $EditUrl, 'DraftLink');
            ?></strong>
            <?php
               echo Anchor(SliceString(Format::Text($Draft->Body), 200), $EditUrl);
            ?>
         </li>
      </ul>
   </li>
   <?php
}