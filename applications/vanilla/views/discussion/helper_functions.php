<?php if (!defined('APPLICATION')) exit();

/**
 * $Object is either a Comment or the original Discussion.
 */
function WriteComment($Object, $Sender, $Session, $CurrentOffset) {
   $Alt = ($CurrentOffset % 2) != 0;

   $Author = UserBuilder($Object, 'Insert');
   $Type = property_exists($Object, 'CommentID') ? 'Comment' : 'Discussion';
	$Sender->EventArguments['Object'] = $Object;
   $Sender->EventArguments['Type'] = $Type;
   $Sender->EventArguments['Author'] = $Author;
   $CssClass = 'Item Comment';
   $Permalink = GetValue('Url', $Object, FALSE);

   if (!property_exists($Sender, 'CanEditComments'))
		$Sender->CanEditComments = $Session->CheckPermission('Vanilla.Comments.Edit', TRUE, 'Category', 'any') && C('Vanilla.AdminCheckboxes.Use');
		

   if ($Type == 'Comment') {
      $Sender->EventArguments['Comment'] = $Object;   
      $Id = 'Comment_'.$Object->CommentID;
      if ($Permalink === FALSE)
         $Permalink = '/discussion/comment/'.$Object->CommentID.'/#Comment_'.$Object->CommentID;
   } else {
      $Sender->EventArguments['Discussion'] = $Object;   
      $CssClass .= ' FirstComment';
      $Id = 'Discussion_'.$Object->DiscussionID;
      if ($Permalink === FALSE)
         $Permalink = '/discussion/'.$Object->DiscussionID.'/'.Gdn_Format::Url($Object->Name).'/p1';
   }
   $Sender->EventArguments['CssClass'] = &$CssClass;
   $Sender->Options = '';
   $CssClass .= $Object->InsertUserID == $Session->UserID ? ' Mine' : '';

   if ($Alt)
      $CssClass .= ' Alt';
   $Alt = !$Alt;
	
	
   $Sender->FireEvent('BeforeCommentDisplay');
?>
<li class="<?php echo $CssClass; ?>" id="<?php echo $Id; ?>">
   <div class="Comment">
      <div class="Meta">
         <?php $Sender->FireEvent('BeforeCommentMeta'); ?>
         <span class="Author">
            <?php
            echo UserPhoto($Author);
            echo UserAnchor($Author);
            ?>
         </span>
         <span class="DateCreated">
            <?php
            echo Anchor(Gdn_Format::Date($Object->DateInserted), $Permalink, 'Permalink', array('name' => 'Item_'.($CurrentOffset+1), 'rel' => 'nofollow'));
            ?>
         </span>
         <?php
			WriteOptionList($Object, $Sender, $Session);
			?>
         <div class="CommentInfo">
            <?php $Sender->FireEvent('CommentInfo'); ?>
         </div>
         <?php $Sender->FireEvent('AfterCommentMeta'); ?>
      </div>
      <div class="Message">
			<?php 
            $Sender->FireEvent('BeforeCommentBody'); 
			   $Object->FormatBody = Gdn_Format::To($Object->Body, $Object->Format);
			   $Sender->FireEvent('AfterCommentFormat');
			   $Object = $Sender->EventArguments['Object'];
			   echo $Object->FormatBody;
			?>
		</div>
      <?php $Sender->FireEvent('AfterCommentBody'); ?>
   </div>
</li>
<?php
	$Sender->FireEvent('AfterComment');
}

function WriteOptionList($Object, $Sender, $Session) {
   $EditContentTimeout = C('Garden.EditContentTimeout', -1);
	$CategoryID = GetValue('CategoryID', $Object);
	if(!$CategoryID && property_exists($Sender, 'Discussion'))
		$CategoryID = GetValue('CategoryID', $Sender->Discussion);
   $PermissionCategoryID = GetValue('PermissionCategoryID', $Object, GetValue('PermissionCategoryID', $Sender->Discussion));

	$CanEdit = $EditContentTimeout == -1 || strtotime($Object->DateInserted) + $EditContentTimeout > time();
	$TimeLeft = '';
	if ($CanEdit && $EditContentTimeout > 0 && !$Session->CheckPermission('Vanilla.Discussions.Edit', TRUE, 'Category', $PermissionCategoryID)) {
		$TimeLeft = strtotime($Object->DateInserted) + $EditContentTimeout - time();
		$TimeLeft = $TimeLeft > 0 ? ' ('.Gdn_Format::Seconds($TimeLeft).')' : '';
	}

   $Sender->Options = '';
		
   // Show discussion options if this is the discussion / first comment
   if ($Sender->EventArguments['Type'] == 'Discussion') {
      // Can the user edit the discussion?
      if (($CanEdit && $Session->UserID == $Object->InsertUserID) || $Session->CheckPermission('Vanilla.Discussions.Edit', TRUE, 'Category', $PermissionCategoryID))
         $Sender->Options .= '<span>'.Anchor(T('Edit'), '/vanilla/post/editdiscussion/'.$Object->DiscussionID, 'EditDiscussion').$TimeLeft.'</span>';
         
      // Can the user announce?
      if ($Session->CheckPermission('Vanilla.Discussions.Announce', TRUE, 'Category', $PermissionCategoryID))
         $Sender->Options .= '<span>'.Anchor(T($Sender->Discussion->Announce == '1' ? 'Unannounce' : 'Announce'), 'vanilla/discussion/announce/'.$Object->DiscussionID.'/'.$Session->TransientKey(), 'AnnounceDiscussion') . '</span>';

      // Can the user sink?
      if ($Session->CheckPermission('Vanilla.Discussions.Sink', TRUE, 'Category', $PermissionCategoryID))
         $Sender->Options .= '<span>'.Anchor(T($Sender->Discussion->Sink == '1' ? 'Unsink' : 'Sink'), 'vanilla/discussion/sink/'.$Object->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl), 'SinkDiscussion') . '</span>';

      // Can the user close?
      if ($Session->CheckPermission('Vanilla.Discussions.Close', TRUE, 'Category', $PermissionCategoryID))
         $Sender->Options .= '<span>'.Anchor(T($Sender->Discussion->Closed == '1' ? 'Reopen' : 'Close'), 'vanilla/discussion/close/'.$Object->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl), 'CloseDiscussion') . '</span>';
      
      // Can the user delete?
      if ($Session->CheckPermission('Vanilla.Discussions.Delete', TRUE, 'Category', $PermissionCategoryID))
         $Sender->Options .= '<span>'.Anchor(T('Delete Discussion'), 'vanilla/discussion/delete/'.$Object->DiscussionID.'/'.$Session->TransientKey(), 'DeleteDiscussion') . '</span>';
   } else {
      // And if this is just another comment in the discussion ...
      
      // Can the user edit the comment?
      if (($CanEdit && $Session->UserID == $Object->InsertUserID) || $Session->CheckPermission('Vanilla.Comments.Edit', TRUE, 'Category', $PermissionCategoryID))
         $Sender->Options .= '<span>'.Anchor(T('Edit'), '/vanilla/post/editcomment/'.$Object->CommentID, 'EditComment').$TimeLeft.'</span>';

      // Can the user delete the comment?
      if ($Session->CheckPermission('Vanilla.Comments.Delete', TRUE, 'Category', $PermissionCategoryID))
         $Sender->Options .= '<span>'.Anchor(T('Delete'), 'vanilla/discussion/deletecomment/'.$Object->CommentID.'/'.$Session->TransientKey().'/?Target='.urlencode("/discussion/{$Object->DiscussionID}/x"), 'DeleteComment') . '</span>';
   }
   
   // Allow plugins to add options
   $Sender->FireEvent('CommentOptions');
   echo $Sender->Options;

   if ($Sender->CanEditComments) {
      if ($Sender->EventArguments['Type'] == 'Comment') {
         $Id = $Object->CommentID;
         echo '<div class="Options">';
         if (!property_exists($Sender, 'CheckedComments'))
            $Sender->CheckedComments = $Session->GetAttribute('CheckedComments', array());

         $ItemSelected = InSubArray($Id, $Sender->CheckedComments);
         echo '<span class="AdminCheck"><input type="checkbox" name="'.'Comment'.'ID[]" value="'.$Id.'"'.($ItemSelected?' checked="checked"':'').' /></span>';
         echo '</div>';
      } else {
         echo '<div class="Options">';

         echo '<div class="AdminCheck"><input type="checkbox" name="Toggle"></div>';

         echo '</div>';
      }
   }
}