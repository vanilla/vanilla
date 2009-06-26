<?php if (!defined('APPLICATION')) exit();

if ($this->_DraftData !== FALSE && $this->_DraftData->NumRows() > 0) {
?>
<div class="Box">
   <h4><?php echo Gdn::Translate('My Drafts'); ?></h4>
   <ul class="PanelDiscussions">
      <?php foreach ($this->_DraftData->Result() as $Draft) {
         $EditUrl = !is_numeric($Draft->DiscussionID) || $Draft->DiscussionID <= 0 ? '/post/editdiscussion/0/'.$Draft->DraftID : '/post/editcomment/0/'.$Draft->DraftID;
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