<?php if (!defined('APPLICATION')) exit();

/**
 * $Object is either a Comment or the original Discussion.
 */
function WriteComment($Object, $Sender, $Session, $CurrentOffset) {
   $Author = UserBuilder($Object, 'Insert');
   $Type = property_exists($Object, 'CommentID') ? 'Comment' : 'Discussion';
   $Sender->EventArguments['Type'] = $Type;
   $CssClass = 'Item Comment';
   if ($Type == 'Comment') {
      $Sender->EventArguments['Comment'] = $Object;   
      $Id = 'Comment_'.$Object->CommentID;
      $Permalink = '/discussion/comment/'.$Object->CommentID.'/#Comment_'.$Object->CommentID;
   } else {
      $Sender->EventArguments['Discussion'] = $Object;   
      $CssClass .= ' FirstComment';
      $Id = 'Discussion_'.$Object->DiscussionID;
      $Permalink = '/discussion/'.$Object->DiscussionID.'/'.Gdn_Format::Url($Object->Name);
   }
   $Sender->Options = '';
   $CssClass .= $Object->InsertUserID == $Session->UserID ? ' Mine' : '';
?>
<li class="<?php echo $CssClass; ?>" id="<?php echo $Id; ?>">
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
            echo Gdn_Format::Date($Object->DateInserted);
            ?>
         </span>
         <span class="Permalink">
            <?php echo Anchor(T('Permalink'), $Permalink, 'Permalink', array('name' => 'Item_'.$CurrentOffset)); ?>
         </span>
         <?php WriteOptionList($Object, $Sender, $Session); ?>
         <?php $Sender->FireEvent('AfterCommentMeta'); ?>
      </div>
      <div class="Message"><?php echo Gdn_Format::To($Object->Body, $Object->Format); ?></div>
      <?php $Sender->FireEvent('AfterCommentBody'); ?>
   </div>
</li>
<?php
}

function WriteOptionList($Object, $Sender, $Session) {
   $Sender->Options = '';
	$CategoryID = GetValue('CategoryID', $Object);
	if(!$CategoryID && property_exists($Sender, 'Discussion'))
		$CategoryID = GetValue('CategoryID', $Sender->Discussion);
		
   // Show discussion options if this is the discussion / first comment
   if ($Sender->EventArguments['Type'] == 'Discussion') {
      // Can the user edit the discussion?
      if ($Session->UserID == $Object->InsertUserID || $Session->CheckPermission('Vanilla.Discussions.Edit', $CategoryID))
         $Sender->Options .= '<span>'.Anchor(T('Edit'), '/vanilla/post/editdiscussion/'.$Object->DiscussionID, 'EditDiscussion').'</span>';
         
      // Can the user announce?
      if ($Session->CheckPermission('Vanilla.Discussions.Announce', $CategoryID))
         $Sender->Options .= '<span>'.Anchor(T($Sender->Discussion->Announce == '1' ? 'Unannounce' : 'Announce'), 'vanilla/discussion/announce/'.$Object->DiscussionID.'/'.$Session->TransientKey(), 'AnnounceDiscussion') . '</span>';

      // Can the user sink?
      if ($Session->CheckPermission('Vanilla.Discussions.Sink', $CategoryID))
         $Sender->Options .= '<span>'.Anchor(T($Sender->Discussion->Sink == '1' ? 'Unsink' : 'Sink'), 'vanilla/discussion/sink/'.$Object->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl), 'SinkDiscussion') . '</span>';

      // Can the user close?
      if ($Session->CheckPermission('Vanilla.Discussions.Close', $CategoryID))
         $Sender->Options .= '<span>'.Anchor(T($Sender->Discussion->Closed == '1' ? 'Reopen' : 'Close'), 'vanilla/discussion/close/'.$Object->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl), 'CloseDiscussion') . '</span>';
      
      // Can the user delete?
      if ($Session->CheckPermission('Vanilla.Discussions.Delete', $CategoryID))
         $Sender->Options .= '<span>'.Anchor(T('Delete Discussion'), 'vanilla/discussion/delete/'.$Object->DiscussionID.'/'.$Session->TransientKey(), 'DeleteDiscussion') . '</span>';
   } else {
      // And if this is just another comment in the discussion ...
      
      // Can the user edit the comment?
      if ($Session->UserID == $Object->InsertUserID || $Session->CheckPermission('Vanilla.Comments.Edit', $Sender->Discussion->CategoryID))
         $Sender->Options .= '<span>'.Anchor(T('Edit'), '/vanilla/post/editcomment/'.$Object->CommentID, 'EditComment').'</span>';

      // Can the user delete the comment?
      if ($Session->CheckPermission('Vanilla.Comments.Delete', $CategoryID))
         $Sender->Options .= '<span>'.Anchor(T('Delete'), 'vanilla/discussion/deletecomment/'.$Object->CommentID.'/'.$Session->TransientKey().'/?Target='.urlencode($Sender->SelfUrl), 'DeleteComment') . '</span>';
   }
   
   // Allow plugins to add options
   $Sender->FireEvent('CommentOptions');
   echo $Sender->Options;
}