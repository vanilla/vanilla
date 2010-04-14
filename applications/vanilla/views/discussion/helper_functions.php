<?php if (!defined('APPLICATION')) exit();

function WriteComment($Comment, &$Sender, &$Session, $CurrentOffset) {
   $Author = UserBuilder($Comment, 'Insert');
   $Sender->EventArguments['Comment'] = &$Comment;
   $Sender->Options = '';
   $CssClass = 'Item Comment';
   $CssClass .= $Comment->InsertUserID == $Session->UserID ? ' Mine' : '';
?>
<li class="<?php echo $CssClass; ?>" id="Comment_<?php echo $Comment->CommentID; ?>">
   <?php // WriteOptionDropdown($Comment, $Sender, $Session); ?>
   <div class="Comment">
      <div class="Meta">
         <span class="Author">
            <?php
            echo UserPhoto($Author);
            echo UserAnchor($Author);
            ?>
         </span>
         <span class="DateCreated">
            <?php
            echo Gdn_Format::Date($Comment->DateInserted);
            ?>
         </span>
         <span class="Permalink">
            <?php echo Anchor(T('Permalink'), '/discussion/comment/'.$Comment->CommentID.'/#Comment_'.$Comment->CommentID, 'Permalink', array('name' => 'Item_'.$CurrentOffset)); ?>
         </span>
         <?php WriteOptionList($Comment, $Sender, $Session); ?>
         <?php $Sender->FireEvent('AfterCommentMeta'); ?>
      </div>
      <div class="Message"><?php echo Gdn_Format::To($Comment->Body, $Comment->Format); ?></div>
      <?php $Sender->FireEvent('AfterCommentBody'); ?>
   </div>
</li>
<?php
}

function WriteOptionDropdown($Comment, &$Sender, &$Session) {
   $Sender->Options = '';
   // Link to the edit discussion form if this is the first comment.
   $IsFirstComment = $Comment->CommentID == $Sender->Discussion->FirstCommentID;
   if ($IsFirstComment && ($Session->UserID == $Comment->InsertUserID || $Session->CheckPermission('Vanilla.Discussions.Edit', $Sender->Discussion->CategoryID))) {
      // User can edit the discussion topic/first comment
      $Sender->Options .= '<li>'.Anchor(T('Edit'), '/vanilla/post/editdiscussion/'.$Comment->DiscussionID, 'EditDiscussion').'</li>';
   } else if ($Session->UserID == $Comment->InsertUserID || $Session->CheckPermission('Vanilla.Comments.Edit', $Sender->Discussion->CategoryID)) {
      // User can edit the comment
      $Sender->Options .= '<li>'.Anchor(T('Edit'), '/vanilla/post/editcomment/'.$Comment->CommentID, 'EditComment').'</li>';
   }
   
   // Show discussion options if this is the first comment
   if ($IsFirstComment) {
      // Announce discussion
      if ($Session->CheckPermission('Vanilla.Discussions.Announce', $Sender->Discussion->CategoryID))
         $Sender->Options .= '<li>'.Anchor(T($Sender->Discussion->Announce == '1' ? 'Unannounce' : 'Announce'), 'vanilla/discussion/announce/'.$Comment->DiscussionID.'/'.$Session->TransientKey(), 'AnnounceDiscussion') . '</li>';

      // Sink discussion
      if ($Session->CheckPermission('Vanilla.Discussions.Sink', $Sender->Discussion->CategoryID))
         $Sender->Options .= '<li>'.Anchor(T($Sender->Discussion->Sink == '1' ? 'Unsink' : 'Sink'), 'vanilla/discussion/sink/'.$Comment->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl), 'SinkDiscussion') . '</li>';

      // Close discussion
      if ($Session->CheckPermission('Vanilla.Discussions.Close', $Sender->Discussion->CategoryID))
         $Sender->Options .= '<li>'.Anchor(T($Sender->Discussion->Closed == '1' ? 'Reopen' : 'Close'), 'vanilla/discussion/close/'.$Comment->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl), 'CloseDiscussion') . '</li>';
      
      // Delete discussion
      if ($Session->CheckPermission('Vanilla.Discussions.Delete', $Sender->Discussion->CategoryID))
         $Sender->Options .= '<li>'.Anchor(T('Delete Discussion'), 'vanilla/discussion/delete/'.$Comment->DiscussionID.'/'.$Session->TransientKey(), 'DeleteDiscussion') . '</li>';
   } else {
      // Delete comment
      if ($Session->CheckPermission('Vanilla.Comments.Delete', $Sender->Discussion->CategoryID))
         $Sender->Options .= '<li>'.Anchor(T('Delete'), 'vanilla/discussion/deletecomment/'.$Comment->CommentID.'/'.$Session->TransientKey().'/?Target='.urlencode($Sender->SelfUrl), 'DeleteComment') . '</li>';
   }
   
   // Allow plugins to add options
   $Sender->FireEvent('CommentOptions');
   
   if ($Sender->Options != '') {
   ?>
   <ul class="Options">
      <li>
         <strong><?php echo T('Options'); ?></strong>
         <ul>
            <?php echo $Sender->Options; ?>
         </ul>
      </li>
   </ul>
   <?php
   }
}

function WriteOptionList($Comment, &$Sender, &$Session) {
   $Sender->Options = '';
   // Link to the edit discussion form if this is the first comment.
   $IsFirstComment = $Comment->CommentID == $Sender->Discussion->FirstCommentID;
   if ($IsFirstComment && ($Session->UserID == $Comment->InsertUserID || $Session->CheckPermission('Vanilla.Discussions.Edit', $Sender->Discussion->CategoryID))) {
      // User can edit the discussion topic/first comment
      $Sender->Options .= '<span>'.Anchor(T('Edit'), '/vanilla/post/editdiscussion/'.$Comment->DiscussionID, 'EditDiscussion').'</span>';
   } else if ($Session->UserID == $Comment->InsertUserID || $Session->CheckPermission('Vanilla.Comments.Edit', $Sender->Discussion->CategoryID)) {
      // User can edit the comment
      $Sender->Options .= '<span>'.Anchor(T('Edit'), '/vanilla/post/editcomment/'.$Comment->CommentID, 'EditComment').'</span>';
   }
   
   // Show discussion options if this is the first comment
   if ($IsFirstComment) {
      // Announce discussion
      if ($Session->CheckPermission('Vanilla.Discussions.Announce', $Sender->Discussion->CategoryID))
         $Sender->Options .= '<span>'.Anchor(T($Sender->Discussion->Announce == '1' ? 'Unannounce' : 'Announce'), 'vanilla/discussion/announce/'.$Comment->DiscussionID.'/'.$Session->TransientKey(), 'AnnounceDiscussion') . '</span>';

      // Sink discussion
      if ($Session->CheckPermission('Vanilla.Discussions.Sink', $Sender->Discussion->CategoryID))
         $Sender->Options .= '<span>'.Anchor(T($Sender->Discussion->Sink == '1' ? 'Unsink' : 'Sink'), 'vanilla/discussion/sink/'.$Comment->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl), 'SinkDiscussion') . '</span>';

      // Close discussion
      if ($Session->CheckPermission('Vanilla.Discussions.Close', $Sender->Discussion->CategoryID))
         $Sender->Options .= '<span>'.Anchor(T($Sender->Discussion->Closed == '1' ? 'Reopen' : 'Close'), 'vanilla/discussion/close/'.$Comment->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl), 'CloseDiscussion') . '</span>';
      
      // Delete discussion
      if ($Session->CheckPermission('Vanilla.Discussions.Delete', $Sender->Discussion->CategoryID))
         $Sender->Options .= '<span>'.Anchor(T('Delete Discussion'), 'vanilla/discussion/delete/'.$Comment->DiscussionID.'/'.$Session->TransientKey(), 'DeleteDiscussion') . '</span>';
   } else {
      // Delete comment
      if ($Session->CheckPermission('Vanilla.Comments.Delete', $Sender->Discussion->CategoryID))
         $Sender->Options .= '<span>'.Anchor(T('Delete'), 'vanilla/discussion/deletecomment/'.$Comment->CommentID.'/'.$Session->TransientKey().'/?Target='.urlencode($Sender->SelfUrl), 'DeleteComment') . '</span>';
   }
   
   // Allow plugins to add options
   $Sender->FireEvent('CommentOptions');
   echo $Sender->Options;
}