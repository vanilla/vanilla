<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
$ShowOptions = TRUE;
$Alt = '';
foreach ($this->DraftData->Result() as $Draft) {
   $EditUrl = $Draft->FirstCommentID == $Draft->CommentID ? '/post/editdiscussion/'.$Draft->DiscussionID : '/post/editcomment/'.$Draft->CommentID;
   $Alt = $Alt == ' Alt' ? '' : ' Alt';
   ?>
   <li class="<?php echo 'DiscussionRow Draft'.$Alt; ?>">
      <ul>
         <li class="Topic">
            <?php
               echo Anchor('Delete', 'vanilla/discussion/deletecomment/'.$Draft->CommentID.'/'.$Session->TransientKey().'?Target='.urlencode($this->SelfUrl), 'DeleteDraft');
            ?>
            <h3><?php
               echo Anchor($Draft->Name, $EditUrl, 'DraftLink');
            ?></h3>
            <?php
               echo Anchor(SliceString(Format::Display($Draft->Body), 200), $EditUrl, 'DraftCommentLink');
            ?>
         </li>
      </ul>
   </li>
   <?php
}