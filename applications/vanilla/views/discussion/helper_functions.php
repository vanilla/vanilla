<?php if (!defined('APPLICATION')) exit();

function WriteComment($Comment, &$Sender, &$Session, $CurrentOffset) {
?>
<li class="Comment<?php echo ($Comment->InsertUserID == $Session->UserID ? ' Mine' : '') ?>" id="Comment_<?php echo $Comment->CommentID; ?>">
   <?php
   $Sender->EventArguments['Comment'] = &$Comment;
   $Sender->Options = '';
   $IsFirstComment = $Comment->CommentID == $Sender->Discussion->FirstCommentID;
   
   if ($IsFirstComment
      && ($Session->UserID == $Comment->InsertUserID
      || $Session->CheckPermission('Vanilla.Discussions.Edit', $Sender->Discussion->CategoryID)))
   {
      // User can edit the discussion topic/first comment
      $Sender->Options .= '<li>'.Anchor(Gdn::Translate('Edit'), '/vanilla/post/editdiscussion/'.$Comment->DiscussionID, 'EditDiscussion').'</li>';
   } else if ($Session->UserID == $Comment->InsertUserID
      || $Session->CheckPermission('Vanilla.Comments.Edit', $Sender->Discussion->CategoryID))
   {
      // User can edit the comment
      $Sender->Options .= '<li>'.Anchor(Gdn::Translate('Edit'), '/vanilla/post/editcomment/'.$Comment->CommentID, 'EditComment').'</li>';
   }
   
   if ($IsFirstComment) {
      // Announce discussion
      if ($Session->CheckPermission('Vanilla.Discussions.Announce', $Sender->Discussion->CategoryID))
         $Sender->Options .= '<li>'.Anchor(Gdn::Translate($Sender->Discussion->Announce == '1' ? 'Unannounce' : 'Announce'), 'vanilla/discussion/announce/'.$Comment->DiscussionID.'/'.$Session->TransientKey(), 'AnnounceDiscussion') . '</li>';

      // Sink discussion
      if ($Session->CheckPermission('Vanilla.Discussions.Sink', $Sender->Discussion->CategoryID))
         $Sender->Options .= '<li>'.Anchor(Gdn::Translate($Sender->Discussion->Sink == '1' ? 'Unsink' : 'Sink'), 'vanilla/discussion/sink/'.$Comment->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl), 'SinkDiscussion') . '</li>';

      // Close discussion
      if ($Session->CheckPermission('Vanilla.Discussions.Close', $Sender->Discussion->CategoryID))
         $Sender->Options .= '<li>'.Anchor(Gdn::Translate($Sender->Discussion->Closed == '1' ? 'Reopen' : 'Close'), 'vanilla/discussion/close/'.$Comment->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl), 'CloseDiscussion') . '</li>';
      
      // Delete discussion
      if ($Session->CheckPermission('Vanilla.Discussions.Delete', $Sender->Discussion->CategoryID))
         $Sender->Options .= '<li>'.Anchor(Gdn::Translate('Delete Discussion'), 'vanilla/discussion/delete/'.$Comment->DiscussionID.'/'.$Session->TransientKey(), 'DeleteDiscussion') . '</li>';
   } else {
      // Delete comment
      if ($Session->CheckPermission('Vanilla.Comments.Delete', $Sender->Discussion->CategoryID))
         $Sender->Options .= '<li>'.Anchor(Gdn::Translate('Delete'), 'vanilla/discussion/deletecomment/'.$Comment->CommentID.'/'.$Session->TransientKey().'/?Target='.urlencode($Sender->SelfUrl), 'DeleteComment') . '</li>';
   }
   
   // Allow plugins to add options
   $Sender->FireEvent('CommentOptions');
   
   if ($Sender->Options != '') {
      ?>
   <ul class="Options">
      <li><strong><?php echo Gdn::Translate('Options'); ?></strong>
         <ul>
            <?php echo $Sender->Options; ?>
         </ul>
      </li>
   </ul>
      <?php
   }
   ?>
   <ul class="Info">
      <li class="Author">
         <?php
         $Author = UserBuilder($Comment, 'Insert');
         echo UserPhoto($Author);
         echo UserAnchor($Author);
         ?>
      </li>
      <li class="Created">
         <?php
         echo Format::Date($Comment->DateInserted);
         ?>
      </li>
      <li class="Permalink">
         <?php echo Anchor(Gdn::Translate('Permalink'), '/discussion/comment/'.$Comment->CommentID.'/#Comment_'.$Comment->CommentID, 'Permalink', array('name' => 'Item_'.$CurrentOffset)); ?>
      </li>
      <?php
      $Sender->FireEvent('AfterCommentMeta');
      ?>
   </ul>
   <div class="Body"><?php echo Format::To($Comment->Body, $Comment->Format); ?></div>
   <?php
      $Sender->FireEvent('AfterCommentBody');
   ?>
</li>
<?php
}