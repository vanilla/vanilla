<?php if (!defined('APPLICATION')) exit();

/**
 * Apply correct classes to the item.
 *
 * @since 2.1
 * @param DataSet $Object Comment or discussion.
 * @param int $CurrentOffset.
 * @return string CSS classes to apply.
 */
function CssClass($Object, $CurrentOffset = 0) {
   $Type = (GetValue('CommentID', $Object)) ? 'Comment' : 'Discussion';
   $CssClass = 'Item Item'.$Type;
   $CssClass .= (GetValue('InsertUserID', $Object) == Gdn::Session()->UserID) ? ' Mine' : '';
   
   if ($Type == 'Comment')
      $CssClass .= ($CurrentOffset % 2) ? ' Alt' : '';
   
   return $CssClass;
}

/**
 * Format content of comment or discussion.
 *
 * Event argument for $Object will be 'Comment' or 'Discussion'.
 *
 * @since 2.1
 * @param DataSet $Object Comment or discussion.
 * @return string Parsed body.
 */
function FormatBody($Object) {
   Gdn::Controller()->FireEvent('BeforeCommentBody'); 
   $Object->FormatBody = Gdn_Format::To($Object->Body, $Object->Format);
   Gdn::Controller()->FireEvent('AfterCommentFormat');
   
   return $Object->FormatBody;
}

/**
 * Output link to (un)boomark a discussion.
 */
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
 * Outputs a formatted comment.
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
   $Author = UserBuilder($Comment, 'Insert');
   $Permalink = GetValue('Url', $Comment, '/discussion/comment/'.$Comment->CommentID.'/#Comment_'.$Comment->CommentID);

   // Set CanEditComments (whether to show checkboxes)
   if (!property_exists($Sender, 'CanEditComments'))
		$Sender->CanEditComments = $Session->CheckPermission('Vanilla.Comments.Edit', TRUE, 'Category', 'any') && C('Vanilla.AdminCheckboxes.Use');
   
   // Prep event args
   $Sender->EventArguments['Comment'] = &$Comment;
   $Sender->EventArguments['Author'] = &$Author;
   $Sender->EventArguments['CssClass'] = &$CssClass;
   
   // DEPRECATED ARGUMENTS (as of 2.1)
	$Sender->EventArguments['Object'] = &$Comment; 
   $Sender->EventArguments['Type'] = 'Comment';
   
   // First comment template event
   $Sender->FireEvent('BeforeCommentDisplay'); ?>
<li class="<?php echo CssClass($Comment, $CurrentOffset); ?>" id="<?php echo 'Comment_'.$Comment->CommentID; ?>">
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
         
         // Add Options
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
         echo FormatBody($Comment);
			?>
		</div>
      <?php $Sender->FireEvent('AfterCommentBody'); ?>
   </div>
</li>
<?php
	$Sender->FireEvent('AfterComment');
}

/**
 * Get options for the current discussion.
 *
 * @since 2.1
 * @param DataSet $Discussion.
 * @return array $Options Each element must include keys 'Label' and 'Url'.
 */
function GetDiscussionOptions($Discussion = NULL) {
   $Options = array();
   
   $Sender = Gdn::Controller();
   $Session = Gdn::Session();
   
   if ($Discussion == NULL)
      $Discussion = $Sender->Data('Discussion');
	
	$CategoryID = GetValue('CategoryID', $Discussion);
	if(!$CategoryID && property_exists($Sender, 'Discussion'))
		$CategoryID = GetValue('CategoryID', $Sender->Discussion);
   $PermissionCategoryID = GetValue('PermissionCategoryID', $Discussion, GetValue('PermissionCategoryID', $Sender->Discussion));
   
   // Determine if we still have time to edit
   $EditContentTimeout = C('Garden.EditContentTimeout', -1);
	$CanEdit = $EditContentTimeout == -1 || strtotime($Discussion->DateInserted) + $EditContentTimeout > time();
	$TimeLeft = '';
	if ($CanEdit && $EditContentTimeout > 0 && !$Session->CheckPermission('Vanilla.Discussions.Edit', TRUE, 'Category', $PermissionCategoryID)) {
		$TimeLeft = strtotime($Discussion->DateInserted) + $EditContentTimeout - time();
		$TimeLeft = $TimeLeft > 0 ? ' ('.Gdn_Format::Seconds($TimeLeft).')' : '';
	}
	
	// Build the $Options array based on current user's permission.
   // Can the user edit the discussion?
   if (($CanEdit && $Session->UserID == $Discussion->InsertUserID) || $Session->CheckPermission('Vanilla.Discussions.Edit', TRUE, 'Category', $PermissionCategoryID))
      $Options['EditDiscussion'] = array('Label' => T('Edit').' '.$TimeLeft, 'Url' => '/vanilla/post/editdiscussion/'.$Discussion->DiscussionID);

   // Can the user announce?
   if ($Session->CheckPermission('Vanilla.Discussions.Announce', TRUE, 'Category', $PermissionCategoryID))
      $Options['AnnounceDiscussion'] = array('Label' => T($Discussion->Announce ? 'Unannounce' : 'Announce'), 'Url' => 'vanilla/discussion/announce/'.$Discussion->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl.'#Head'), 'Class' => 'Hijack');

   // Can the user sink?
   if ($Session->CheckPermission('Vanilla.Discussions.Sink', TRUE, 'Category', $PermissionCategoryID))
      $Options['SinkDiscussion'] = array('Label' => T($Discussion->Sink ? 'Unsink' : 'Sink'), 'Url' => 'vanilla/discussion/sink/'.$Discussion->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl.'#Head'), 'Class' => 'Hijack');

   // Can the user close?
   if ($Session->CheckPermission('Vanilla.Discussions.Close', TRUE, 'Category', $PermissionCategoryID))
      $Options['CloseDiscussion'] = array('Label' => T($Discussion->Closed ? 'Reopen' : 'Close'), 'Url' => 'vanilla/discussion/close/'.$Discussion->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl.'#Head'), 'Class' => 'Hijack');

   // Can the user delete?
   if ($Session->CheckPermission('Vanilla.Discussions.Delete', TRUE, 'Category', $PermissionCategoryID))
      $Options['DeleteDiscussion'] = array('Label' => T('Delete Discussion'), 'Url' => 'vanilla/discussion/delete/'.$Discussion->DiscussionID.'/'.$Session->TransientKey());
   
   // DEPRECATED (as of 2.1)
   $Sender->EventArguments['Type'] = 'Discussion';
   
   // Allow plugins to add options.
   $Sender->EventArguments['DiscussionOptions'] = &$Options;
   $Sender->EventArguments['Discussion'] = $Discussion;
   $Sender->FireEvent('DiscussionOptions');
   
   return $Options;
}

/**
 * Output moderation checkbox.
 *
 * @since 2.1
 */
function WriteAdminCheck($Object = NULL) {
   if (!Gdn::Controller()->CanEditComments || !C('Vanilla.AdminCheckboxes.Use'))
      return;
   
   echo '<span class="AdminCheck"><input type="checkbox" name="Toggle"></span>';
}

/**
 * Output discussion options.
 *
 * @since 2.1
 */
function WriteDiscussionOptions($Discussion = NULL) {
   $Options = GetDiscussionOptions($Discussion);
   
   if (empty($Options))
      return;
   ?>
   <span class="ToggleFlyout OptionsMenu">
      <span class="OptionsTitle" title="<?php echo T('Options'); ?>"><?php echo T('Options'); ?></span>
      <ul class="Flyout MenuItems" style="display: none;">
      <?php foreach ($Options as $Code => $Option) : ?>
			<li><?php echo Anchor($Option['Label'], $Option['Url'], GetValue('Class', $Option, $Code)); ?></li>
		<?php endforeach; ?>
      </ul>
   </span>
   <?php
}

/**
 * Get comment options.
 *
 * @since 2.1
 * @param DataSet $Comment.
 * @return array $Options Each element must include keys 'Label' and 'Url'.
 */
function GetCommentOptions($Comment) {
	$Options = array();
   
   if (!is_numeric(GetValue('CommentID', $Comment)))
      return $Options;
   
   $Sender = Gdn::Controller();
   $Session = Gdn::Session();
	$Discussion = Gdn::Controller()->Data('Discussion');
	
	$CategoryID = GetValue('CategoryID', $Discussion);
   $PermissionCategoryID = GetValue('PermissionCategoryID', $Discussion);
   
   // Determine if we still have time to edit
   $EditContentTimeout = C('Garden.EditContentTimeout', -1);
	$CanEdit = $EditContentTimeout == -1 || strtotime($Comment->DateInserted) + $EditContentTimeout > time();
	$TimeLeft = '';
	if ($CanEdit && $EditContentTimeout > 0 && !$Session->CheckPermission('Vanilla.Discussions.Edit', TRUE, 'Category', $PermissionCategoryID)) {
		$TimeLeft = strtotime($Comment->DateInserted) + $EditContentTimeout - time();
		$TimeLeft = $TimeLeft > 0 ? ' ('.Gdn_Format::Seconds($TimeLeft).')' : '';
	}
	
	// Can the user edit the comment?
	if (($CanEdit && $Session->UserID == $Comment->InsertUserID) || $Session->CheckPermission('Vanilla.Comments.Edit', TRUE, 'Category', $PermissionCategoryID))
		$Options['EditComment'] = array('Label' => T('Edit').' '.$TimeLeft, 'Url' => '/vanilla/post/editcomment/'.$Comment->CommentID, 'EditComment');

	// Can the user delete the comment?
	if (($CanEdit && $Session->UserID == $Comment->InsertUserID) || $Session->CheckPermission('Vanilla.Comments.Delete', TRUE, 'Category', $PermissionCategoryID))
		$Options['DeleteComment'] = array('Label' => T('Delete'), 'Url' => 'vanilla/discussion/deletecomment/'.$Comment->CommentID.'/'.$Session->TransientKey().'/?Target='.urlencode("/discussion/{$Comment->DiscussionID}/x"), 'Class' => 'DeleteComment');
   
   // DEPRECATED (as of 2.1)
   $Sender->EventArguments['Type'] = 'Comment';
   
   // Allow plugins to add options
	$Sender->EventArguments['CommentOptions'] = &$Options;
	$Sender->EventArguments['Comment'] = $Comment;
   $Sender->FireEvent('CommentOptions');
   
	return $Options;
}

/**
 * Output comment options.
 *
 * @since 2.1
 * @param DataSet $Comment.
 */
function WriteCommentOptions($Comment) {
	$Controller = Gdn::Controller();
	$Session = Gdn::Session();
	
   $Id = $Comment->CommentID;
	$Options = GetCommentOptions($Comment);
	if (empty($Options))
		return;
   ?>
   <div class="Options">
      <span class="ToggleFlyout OptionsMenu">
         <span class="OptionsTitle" title="<?php echo T('Options'); ?>"><?php echo T('Options'); ?></span>
			<ul class="Flyout MenuItems">
         <?php foreach ($Options as $Code => $Option) : ?>
				<li><?php echo Anchor($Option['Label'], $Option['Url'], GetValue('Class', $Option, $Code)); ?></li>
         <?php endforeach; ?>
			</ul>
		</span>
		<?php
      if (C('Vanilla.AdminCheckboxes.Use')) {
		   if (!property_exists($Controller, 'CheckedComments'))
				$Controller->CheckedComments = $Session->GetAttribute('CheckedComments', array());
	
			$ItemSelected = InSubArray($Id, $Controller->CheckedComments);
			echo '<span class="AdminCheck"><input type="checkbox" name="'.'Comment'.'ID[]" value="'.$Id.'"'.($ItemSelected?' checked="checked"':'').' /></span>';
		}
		?>
   </div>
   <?php
}

/**
 * Output comment form.
 *
 * @since 2.1
 */
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