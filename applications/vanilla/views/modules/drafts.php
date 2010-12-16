<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Box BoxDrafts">
   <h4><?php echo T('My Drafts'); ?></h4>
   <ul class="PanelInfo PanelDiscussions">
      <?php foreach ($this->Data->Result() as $Draft) {
         $EditUrl = !is_numeric($Draft->DiscussionID) || $Draft->DiscussionID <= 0 ? '/post/editdiscussion/0/'.$Draft->DraftID : '/post/editcomment/0/'.$Draft->DraftID;
      ?>
      <li>
         <strong><?php echo Anchor($Draft->Name, $EditUrl); ?></strong>
         <?php echo Anchor(SliceString(Gdn_Format::Text($Draft->Body), 200), $EditUrl, 'DraftCommentLink'); ?>
      </li>
      <?php
      } 
      ?>
      <li class="ShowAll"><?php echo Anchor(T('↳ Show All'), 'drafts'); ?></li>
   </ul>
</div>