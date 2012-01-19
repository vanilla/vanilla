<?php if (!defined('APPLICATION')) exit();

function CssClass($Row) {
   
}

function FormatBody($Row) {
   $Row->FormatBody = Gdn_Format::To($Row->Body, $Row->Format);
   Gdn::Controller()->EventArguments['Object'] = $Row;
   Gdn::Controller()->FireEvent('AfterCommentFormat');
   
   return $Row->FormatBody;
}

function WriteBookmarkLink() {
   if (!Gdn::Session()->IsValid())
      return '';
   
   $Discussion = Gdn::Controller()->Data('Discussion');

   // Bookmark link
	$Title = T($Discussion->Bookmarked == '1' ? 'Unbookmark' : 'Bookmark');
   echo Anchor(
      $Title,
      '/vanilla/discussion/bookmark/'.$Discussion->DiscussionID.'/'.Gdn::Session()->TransientKey().'?Target='.urlencode(Gdn::Controller()->SelfUrl),
      'Bookmark' . ($Discussion->Bookmarked == '1' ? ' Bookmarked' : ''),
      array('title' => $Title)
   );
}

/**
 * Outputs a formatted comment to the browser.
 *
 * Prior to 2.1, this also output the discussion ("FirstComment") to the browser.
 * That has moved to the discussion.php view.
 * 
 * @param DataSet $Comment.
 * @param Gdn_Controller $Sender.
 * @param Gdn_Session $Session.
 * @param int $CurrentOffet How many comments into the discussion we are (for anchors).
 */
function WriteComment($Comment, $Sender, $Session, $CurrentOffset) {
   // Build author
   $Author = UserBuilder($Comment, 'Insert');
   
   // Set CSS classes
   $CssClass = 'Item ItemComment';
   $CssClass .= $Comment->InsertUserID == $Session->UserID ? ' Mine' : '';
   
   // Alternate comments
   $Alt = ($CurrentOffset % 2) != 0;
   if ($Alt)
      $CssClass .= ' Alt';
   $Alt = !$Alt;
   
   // Reset options
   $Sender->Options = '';
   
   // Build permalink
   $Permalink = GetValue('Url', $Comment, FALSE);
   $Id = 'Comment_'.$Comment->CommentID;
   if ($Permalink === FALSE)
      $Permalink = '/discussion/comment/'.$Comment->CommentID.'/#Comment_'.$Comment->CommentID; 
   
   // Set CanEditComments (whether to show checkboxes)
   if (!property_exists($Sender, 'CanEditComments'))
		$Sender->CanEditComments = $Session->CheckPermission('Vanilla.Comments.Edit', TRUE, 'Category', 'any') && C('Vanilla.AdminCheckboxes.Use');
   
   // Prep event args
   $Sender->EventArguments['Comment'] = &$Comment;
   $Sender->EventArguments['Author'] = &$Author;
   $Sender->EventArguments['CssClass'] = &$CssClass;
   $Sender->EventArguments['Permalink'] = &$Permalink;
   
   // DEPRECATED ARGUMENTS (as of 2.1)
	$Sender->EventArguments['Object'] = &$Comment; 
   $Sender->EventArguments['Type'] = 'Comment';
   
   // First comment template event; best place to modify Options array
   $Sender->FireEvent('BeforeCommentDisplay'); ?>
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
         <span class="MItem DateCreated">
            <?php echo Anchor(Gdn_Format::Date($Comment->DateInserted, 'html'), $Permalink, 'Permalink', array('name' => 'Item_'.($CurrentOffset), 'rel' => 'nofollow')); ?>
         </span>
         <?php
         // Include source if one was set
         if ($Source = GetValue('Source', $Comment))
            echo Wrap(sprintf(T('via %s'), T($Source.' Source', $Source)), 'span', array('class' => 'MItem Source'));
         
         // Add your own options or data as spans with 'MItem' class
         $Sender->FireEvent('InsideCommentMeta');
         
			WriteCommentOptions($Comment);
			?>
         <div class="CommentInfo">
            <?php
            $Sender->FireEvent('CommentInfo');
            
            // Include IP Address if we have permission
            if ($Session->CheckPermission('Garden.Moderation.Manage')) 
               echo Wrap(IPAnchor($Comment->InsertIPAddress), 'span', array('class' => 'MItem IPAddress'));
            ?>
         </div>
         <?php $Sender->FireEvent('AfterCommentMeta'); ?>
      </div>
      <div class="Message">
			<?php 
            $Sender->FireEvent('BeforeCommentBody'); 
			   $Comment->FormatBody = Gdn_Format::To($Comment->Body, $Comment->Format);
			   $Sender->FireEvent('AfterCommentFormat'); // Use to override comment formatting
			   echo $Comment->FormatBody;
			?>
		</div>
      <?php $Sender->FireEvent('AfterCommentBody'); ?>
   </div>
</li>
<?php
	$Sender->FireEvent('AfterComment');
}

function GetDiscussionOptions($Discussion = NULL) {
   $Result = array();
   
   $Sender = Gdn::Controller();
   $Session = Gdn::Session();
   
   if ($Discussion == NULL)
      $Discussion = $Sender->Data('Discussion');
   
   $EditContentTimeout = C('Garden.EditContentTimeout', -1);
	$CategoryID = GetValue('CategoryID', $Discussion);
	if(!$CategoryID && property_exists($Sender, 'Discussion'))
		$CategoryID = GetValue('CategoryID', $Sender->Discussion);
   $PermissionCategoryID = GetValue('PermissionCategoryID', $Discussion, GetValue('PermissionCategoryID', $Sender->Discussion));

	$CanEdit = $EditContentTimeout == -1 || strtotime($Discussion->DateInserted) + $EditContentTimeout > time();
	$TimeLeft = '';
	if ($CanEdit && $EditContentTimeout > 0 && !$Session->CheckPermission('Vanilla.Discussions.Edit', TRUE, 'Category', $PermissionCategoryID)) {
		$TimeLeft = strtotime($Discussion->DateInserted) + $EditContentTimeout - time();
		$TimeLeft = $TimeLeft > 0 ? ' ('.Gdn_Format::Seconds($TimeLeft).')' : '';
	}
   
   // Can the user edit the discussion?
   if (($CanEdit && $Session->UserID == $Discussion->InsertUserID) || $Session->CheckPermission('Vanilla.Discussions.Edit', TRUE, 'Category', $PermissionCategoryID))
      $Result['EditDiscussion'] = array('Label' => T('Edit').' '.$TimeLeft, 'Url' => '/vanilla/post/editdiscussion/'.$Discussion->DiscussionID);

   // Can the user announce?
   if ($Session->CheckPermission('Vanilla.Discussions.Announce', TRUE, 'Category', $PermissionCategoryID))
      $Result['AnnounceDiscussion'] = array('Label' => T($Sender->Discussion->Announce ? 'Unannounce' : 'Announce'), 'Url' => 'vanilla/discussion/announce/'.$Discussion->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl.'#Head'), 'Class' => 'Hijack');

   // Can the user sink?
   if ($Session->CheckPermission('Vanilla.Discussions.Sink', TRUE, 'Category', $PermissionCategoryID))
      $Result['SinkDiscussion'] = array('Label' => T($Sender->Discussion->Sink ? 'Unsink' : 'Sink'), 'Url' => 'vanilla/discussion/sink/'.$Discussion->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl.'#Head'), 'Class' => 'Hijack');

   // Can the user close?
   if ($Session->CheckPermission('Vanilla.Discussions.Close', TRUE, 'Category', $PermissionCategoryID))
      $Result['CloseDiscussion'] = array('Label' => T($Sender->Discussion->Closed ? 'Reopen' : 'Close'), 'Url' => 'vanilla/discussion/close/'.$Discussion->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl.'#Head'), 'Class' => 'Hijack');

   // Can the user delete?
   if ($Session->CheckPermission('Vanilla.Discussions.Delete', TRUE, 'Category', $PermissionCategoryID))
      $Result['DeleteDiscussion'] = array('Label' => T('Delete Discussion'), 'Url' => 'vanilla/discussion/delete/'.$Discussion->DiscussionID.'/'.$Session->TransientKey());
   
   // Allow plugins to add options.
   $Sender->EventArguments['Options'] =& $Result;
   $Sender->FireEvent('DiscussionOptions');
   
   return $Result;
}

function WriteAdminCheck($Row = NULL) {
   if (!Gdn::Controller()->CanEditComments || !C('Vanilla.AdminCheckboxes.Use'))
      return;
   
   echo '<span class="AdminCheck"><input type="checkbox" name="Toggle"></span>';
}

function WriteDiscussionOptions($Discussion = NULL) {
   $Options = GetDiscussionOptions();
   
   if (empty($Options))
      return;
   
   echo '<span class="ToggleFlyout OptionsMenu">';
		echo '<span class="OptionsTitle" title="'.T('Options').'">'.T('Options').'</span>';
		echo '<ul class="Flyout MenuItems">';
		foreach ($Options as $Code => $Option) {
			echo '<li>'.Anchor($Option['Label'], $Option['Url'], GetValue('Class', $Option, $Code)).'</li>';
		}
		echo '</ul>';
   echo '</span>';
}

function GetCommentOptions($Comment = NULL) {
	$Options = array();
   
   if (!is_numeric(GetValue('CommentID', $Comment)))
      return $Options;
   
   $Sender = Gdn::Controller();
   $Session = Gdn::Session();
	$Discussion = Gdn::Controller()->Data('Discussion');
   
   $EditContentTimeout = C('Garden.EditContentTimeout', -1);
	$CategoryID = GetValue('CategoryID', $Discussion);
   $PermissionCategoryID = GetValue('PermissionCategoryID', $Discussion);
	$CanEdit = $EditContentTimeout == -1 || strtotime($Comment->DateInserted) + $EditContentTimeout > time();
	$TimeLeft = '';
	if ($CanEdit && $EditContentTimeout > 0 && !$Session->CheckPermission('Vanilla.Discussions.Edit', TRUE, 'Category', $PermissionCategoryID)) {
		$TimeLeft = strtotime($Comment->DateInserted) + $EditContentTimeout - time();
		$TimeLeft = $TimeLeft > 0 ? ' ('.Gdn_Format::Seconds($TimeLeft).')' : '';
	}

   $Sender->EventArguments['Type'] = 'Comment';
	$Sender->EventArguments['Comment'] = $Comment;
	
	// Can the user edit the comment?
	if (($CanEdit && $Session->UserID == $Comment->InsertUserID) || $Session->CheckPermission('Vanilla.Comments.Edit', TRUE, 'Category', $PermissionCategoryID))
		$Options['EditComment'] = array('Label' => T('Edit').' '.$TimeLeft, 'Url' => '/vanilla/post/editcomment/'.$Comment->CommentID, 'EditComment');

	// Can the user delete the comment?
	if (($CanEdit && $Session->UserID == $Comment->InsertUserID) || $Session->CheckPermission('Vanilla.Comments.Delete', TRUE, 'Category', $PermissionCategoryID))
		$Options['DeleteComment'] = array('Label' => T('Delete'), 'Url' => 'vanilla/discussion/deletecomment/'.$Comment->CommentID.'/'.$Session->TransientKey().'/?Target='.urlencode("/discussion/{$Comment->DiscussionID}/x"), 'Class' => 'DeleteComment');
   
   // Allow plugins to add options
	$Sender->EventArguments['CommentOptions'] = $Options;
   $Sender->FireEvent('CommentOptions');
   
	return $Options;
}

function WriteCommentOptions($Comment) {
	$Controller = Gdn::Controller();
   //if (GetValue('CanEditComments', $Controller)) {
      $Id = $Comment->CommentID;
		$Options = GetCommentOptions($Comment);
		$Session = Gdn::Session();
		if (empty($Options))
			return;

      echo '<div class="Options">';
		   echo '<span class="ToggleFlyout OptionsMenu">';
				echo '<span class="OptionsTitle" title="'.T('Options').'">'.T('Options').'</span>';
				echo '<ul class="Flyout MenuItems" style="display: none;">';
					foreach ($Options as $Code => $Option) {
						echo '<li>'.Anchor($Option['Label'], $Option['Url'], GetValue('Class', $Option, $Code)).'</li>';
					}
				echo '</ul>';
			echo '</span>';
         if (C('Vanilla.AdminCheckboxes.Use')) {
   		   if (!property_exists($Controller, 'CheckedComments'))
   				$Controller->CheckedComments = $Session->GetAttribute('CheckedComments', array());
   	
   			$ItemSelected = InSubArray($Id, $Controller->CheckedComments);
   			echo '<span class="AdminCheck"><input type="checkbox" name="'.'Comment'.'ID[]" value="'.$Id.'"'.($ItemSelected?' checked="checked"':'').' /></span>';
			}
      echo '</div>';
   //}
}

function WriteCommentForm() {
	$Session = Gdn::Session();
	$Controller = Gdn::Controller();
	$Discussion = $Controller->Data('Discussion');
	$PermissionCategoryID = GetValue('PermissionCategoryID', $Discussion);
	
	// Closed notification
	if ($Discussion->Closed == '1') {
		?>
		<div class="Foot Closed">
			<div class="Note Closed"><?php echo T('This discussion has been closed.'); ?></div>
			<?php echo Anchor(T('All Discussions'), 'discussions', 'TabLink'); ?>
		</div>
		<?php
	} 
	
	// Comment form
	if (!$Discussion->Closed || $Session->CheckPermission('Vanilla.Discussions.Close', TRUE, 'Category', $PermissionCategoryID))
		echo $Controller->FetchView('comment', 'post');
}