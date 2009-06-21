<?php if (!defined('APPLICATION')) exit();

if ($this->_DraftData !== FALSE && $this->_DraftData->NumRows() > 0) {
?>
<div class="Box">
   <h4><?php echo Gdn::Translate('My Drafts'); ?></h4>
   <ul class="PanelDiscussions">
      <?php foreach ($this->_DraftData->Result() as $Draft) {
         $EditUrl = $Draft->FirstCommentID == $Draft->CommentID ? '/post/editdiscussion/'.$Draft->DiscussionID : '/post/editcomment/'.$Draft->CommentID;
      ?>
      <li>
         <ul>
            <li class="Topic">
               <?php
                  echo Anchor($Draft->Name, $EditUrl, 'DraftLink');
               ?>
            </li>
            <li class="Body">
               <?php
                  echo Anchor(SliceString(Format::Text($Draft->Body), 200), $EditUrl, 'DraftCommentLink');
               ?>
            </li>
         </ul>
      </li>
      <?php } ?>
   </ul>
</div>
<?php
}