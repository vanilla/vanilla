<?php if (!defined('APPLICATION')) exit();

/**
 * Format content of comment or discussion.
 *
 * Event argument for $Object will be 'Comment' or 'Discussion'.
 *
 * @since 2.1
 * @param DataSet $Object Comment or discussion.
 * @return string Parsed body.
 */
if (!function_exists('FormatBody')):
function FormatBody($Object) {
   Gdn::Controller()->FireEvent('BeforeCommentBody');
   $Object->FormatBody = Gdn_Format::To($Object->Body, $Object->Format);
   Gdn::Controller()->FireEvent('AfterCommentFormat');

   return $Object->FormatBody;
}
endif;

/**
 * Output link to (un)boomark a discussion.
 */
if (!function_exists('WriteBookmarkLink')):
function WriteBookmarkLink() {
   if (!Gdn::Session()->IsValid())
      return '';

   $Discussion = Gdn::Controller()->Data('Discussion');

   // Bookmark link
   $Title = T($Discussion->Bookmarked == '1' ? 'Unbookmark' : 'Bookmark');
   echo Anchor(
      $Title,
      '/vanilla/discussion/bookmark/'.$Discussion->DiscussionID.'/'.Gdn::Session()->TransientKey().'?Target='.urlencode(Gdn::Controller()->SelfUrl),
      'Hijack Bookmark' . ($Discussion->Bookmarked == '1' ? ' Bookmarked' : ''),
      array('title' => $Title)
   );
}
endif;

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
if (!function_exists('WriteComment')):
function WriteComment($Comment, $Sender, $Session, $CurrentOffset) {
   static $UserPhotoFirst = NULL;
   if ($UserPhotoFirst === NULL)
      $UserPhotoFirst = C('Vanilla.Comment.UserPhotoFirst', TRUE);
   $Author = Gdn::UserModel()->GetID($Comment->InsertUserID); //UserBuilder($Comment, 'Insert');
   $Permalink = GetValue('Url', $Comment, '/discussion/comment/'.$Comment->CommentID.'/#Comment_'.$Comment->CommentID);

   // Set CanEditComments (whether to show checkboxes)
   if (!property_exists($Sender, 'CanEditComments'))
		$Sender->CanEditComments = $Session->CheckPermission('Vanilla.Comments.Edit', TRUE, 'Category', 'any') && C('Vanilla.AdminCheckboxes.Use');

   // Prep event args
   $CssClass = CssClass($Comment, $CurrentOffset);
   $Sender->EventArguments['Comment'] = &$Comment;
   $Sender->EventArguments['Author'] = &$Author;
   $Sender->EventArguments['CssClass'] = &$CssClass;

   // DEPRECATED ARGUMENTS (as of 2.1)
	$Sender->EventArguments['Object'] = &$Comment;
   $Sender->EventArguments['Type'] = 'Comment';

   // First comment template event
   $Sender->FireEvent('BeforeCommentDisplay'); ?>
<li class="<?php echo $CssClass; ?>" id="<?php echo 'Comment_'.$Comment->CommentID; ?>">
   <div class="Comment">

      <?php
      // Write a stub for the latest comment so it's easy to link to it from outside.
      if ($CurrentOffset == Gdn::Controller()->Data('_LatestItem')) {
         echo '<span id="latest"></span>';
      }
      ?>
      <div class="Options">
         <?php WriteCommentOptions($Comment); ?>
      </div>
      <?php $Sender->FireEvent('BeforeCommentMeta'); ?>
      <div class="Item-Header CommentHeader">
         <div class="AuthorWrap">
            <span class="Author">
               <?php
               if ($UserPhotoFirst) {
                  echo UserPhoto($Author);
                  echo UserAnchor($Author, 'Username');
               } else {
                  echo UserAnchor($Author, 'Username');
                  echo UserPhoto($Author);
               }
               echo FormatMeAction($Comment);
               $Sender->FireEvent('AuthorPhoto');
               ?>
            </span>
            <span class="AuthorInfo">
               <?php
               echo ' '.WrapIf(htmlspecialchars(GetValue('Title', $Author)), 'span', array('class' => 'MItem AuthorTitle'));
               echo ' '.WrapIf(htmlspecialchars(GetValue('Location', $Author)), 'span', array('class' => 'MItem AuthorLocation'));
               $Sender->FireEvent('AuthorInfo');
               ?>
            </span>
         </div>
         <div class="Meta CommentMeta CommentInfo">
            <span class="MItem DateCreated">
               <?php echo Anchor(Gdn_Format::Date($Comment->DateInserted, 'html'), $Permalink, 'Permalink', array('name' => 'Item_'.($CurrentOffset), 'rel' => 'nofollow')); ?>
            </span>
            <?php
               echo DateUpdated($Comment, array('<span class="MItem">', '</span>'));
            ?>
            <?php
            // Include source if one was set
            if ($Source = GetValue('Source', $Comment))
               echo Wrap(sprintf(T('via %s'), T($Source.' Source', $Source)), 'span', array('class' => 'MItem Source'));

            $Sender->FireEvent('CommentInfo');
            $Sender->FireEvent('InsideCommentMeta'); // DEPRECATED
            $Sender->FireEvent('AfterCommentMeta'); // DEPRECATED

            // Include IP Address if we have permission
            if ($Session->CheckPermission('Garden.PersonalInfo.View'))
               echo Wrap(IPAnchor($Comment->InsertIPAddress), 'span', array('class' => 'MItem IPAddress'));

            ?>
         </div>
      </div>
      <div class="Item-BodyWrap">
         <div class="Item-Body">
            <div class="Message">
               <?php
                  echo FormatBody($Comment);
               ?>
            </div>
            <?php
            $Sender->FireEvent('AfterCommentBody');
            WriteReactions($Comment);
            ?>
         </div>
      </div>
   </div>
</li>
<?php
	$Sender->FireEvent('AfterComment');
}
endif;

/**
 * Get options for the current discussion.
 *
 * @since 2.1
 * @param DataSet $Discussion.
 * @return array $Options Each element must include keys 'Label' and 'Url'.
 */
if (!function_exists('GetDiscussionOptions')):
function GetDiscussionOptions($Discussion = NULL) {
   $Options = array();

   $Sender = Gdn::Controller();
   $Session = Gdn::Session();

   if ($Discussion == NULL)
      $Discussion = $Sender->Data('Discussion');

	$CategoryID = GetValue('CategoryID', $Discussion);
	if(!$CategoryID && property_exists($Sender, 'Discussion'))
		$CategoryID = GetValue('CategoryID', $Sender->Discussion);
   $PermissionCategoryID = GetValue('PermissionCategoryID', $Discussion, GetValue('PermissionCategoryID', $Discussion));

   // Determine if we still have time to edit
   $EditContentTimeout = C('Garden.EditContentTimeout', -1);
	$CanEdit = $EditContentTimeout == -1 || strtotime($Discussion->DateInserted) + $EditContentTimeout > time();
   $CanEdit = ($CanEdit && $Session->UserID == $Discussion->InsertUserID) || $Session->CheckPermission('Vanilla.Discussions.Edit', TRUE, 'Category', $PermissionCategoryID);

	$TimeLeft = '';

	if ($CanEdit && $EditContentTimeout > 0 && !$Session->CheckPermission('Vanilla.Discussions.Edit', TRUE, 'Category', $PermissionCategoryID)) {
		$TimeLeft = strtotime($Discussion->DateInserted) + $EditContentTimeout - time();
		$TimeLeft = $TimeLeft > 0 ? ' ('.Gdn_Format::Seconds($TimeLeft).')' : '';
	}

	// Build the $Options array based on current user's permission.
   // Can the user edit the discussion?
   if ($CanEdit)
      $Options['EditDiscussion'] = array('Label' => T('Edit').' '.$TimeLeft, 'Url' => '/vanilla/post/editdiscussion/'.$Discussion->DiscussionID);

   // Can the user announce?
   if ($Session->CheckPermission('Vanilla.Discussions.Announce', TRUE, 'Category', $PermissionCategoryID))
      $Options['AnnounceDiscussion'] = array('Label' => T('Announce'), 'Url' => 'vanilla/discussion/announce?discussionid='.$Discussion->DiscussionID.'&Target='.urlencode($Sender->SelfUrl.'#Head'), 'Class' => 'Popup');

   // Can the user sink?
   if ($Session->CheckPermission('Vanilla.Discussions.Sink', TRUE, 'Category', $PermissionCategoryID)) {
      $NewSink = (int)!$Discussion->Sink;
      $Options['SinkDiscussion'] = array('Label' => T($Discussion->Sink ? 'Unsink' : 'Sink'), 'Url' => "/discussion/sink?discussionid={$Discussion->DiscussionID}&sink=$NewSink", 'Class' => 'Hijack');
   }

   // Can the user close?
   if ($Session->CheckPermission('Vanilla.Discussions.Close', TRUE, 'Category', $PermissionCategoryID)) {
      $NewClosed = (int)!$Discussion->Closed;
      $Options['CloseDiscussion'] = array('Label' => T($Discussion->Closed ? 'Reopen' : 'Close'), 'Url' => "/discussion/close?discussionid={$Discussion->DiscussionID}&close=$NewClosed", 'Class' => 'Hijack');
   }

   if ($CanEdit && GetValueR('Attributes.ForeignUrl', $Discussion)) {
      $Options['RefetchPage'] = array('Label' => T('Refetch Page'), 'Url' => '/discussion/refetchpageinfo.json?discussionid='.$Discussion->DiscussionID, 'Class' => 'Hijack');
   }

   // Can the user move?
   if ($CanEdit && $Session->CheckPermission('Garden.Moderation.Manage')) {
      $Options['MoveDiscussion'] = array('Label' => T('Move'), 'Url' => '/moderation/confirmdiscussionmoves?discussionid='.$Discussion->DiscussionID, 'Class' => 'Popup');
   }

   // Can the user delete?
   if ($Session->CheckPermission('Vanilla.Discussions.Delete', TRUE, 'Category', $PermissionCategoryID)) {
      $Category = CategoryModel::Categories($CategoryID);

      $Options['DeleteDiscussion'] = array('Label' => T('Delete Discussion'), 'Url' => '/discussion/delete?discussionid='.$Discussion->DiscussionID.'&target='.urlencode(CategoryUrl($Category)), 'Class' => 'Popup');
   }

   // DEPRECATED (as of 2.1)
   $Sender->EventArguments['Type'] = 'Discussion';

   // Allow plugins to add options.
   $Sender->EventArguments['DiscussionOptions'] = &$Options;
   $Sender->EventArguments['Discussion'] = $Discussion;
   $Sender->FireEvent('DiscussionOptions');

   return $Options;
}
endif;

/**
 * Output moderation checkbox.
 *
 * @since 2.1
 */
if (!function_exists('WriteAdminCheck')):
function WriteAdminCheck($Object = NULL) {
   if (!Gdn::Controller()->CanEditComments || !C('Vanilla.AdminCheckboxes.Use'))
      return;

   echo '<span class="AdminCheck"><input type="checkbox" name="Toggle"></span>';
}
endif;

/**
 * Output discussion options.
 *
 * @since 2.1
 */
if (!function_exists('WriteDiscussionOptions')):
function WriteDiscussionOptions($Discussion = NULL) {
   $Options = GetDiscussionOptions($Discussion);

   if (empty($Options))
      return;

   echo ' <span class="ToggleFlyout OptionsMenu">';
      echo '<span class="OptionsTitle" title="'.T('Options').'">'.T('Options').'</span>';
		echo Sprite('SpFlyoutHandle', 'Arrow');
      echo '<ul class="Flyout MenuItems" style="display: none;">';
      foreach ($Options as $Code => $Option):
			echo Wrap(Anchor($Option['Label'], $Option['Url'], GetValue('Class', $Option, $Code)), 'li');
		endforeach;
      echo '</ul>';
   echo '</span>';
}
endif;

/**
 * Get comment options.
 *
 * @since 2.1
 * @param DataSet $Comment.
 * @return array $Options Each element must include keys 'Label' and 'Url'.
 */
if (!function_exists('GetCommentOptions')):
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
	$SelfDeleting = ($CanEdit && $Session->UserID == $Comment->InsertUserID && C('Vanilla.Comments.AllowSelfDelete'));
   if ($SelfDeleting || $Session->CheckPermission('Vanilla.Comments.Delete', TRUE, 'Category', $PermissionCategoryID))
		$Options['DeleteComment'] = array('Label' => T('Delete'), 'Url' => 'vanilla/discussion/deletecomment/'.$Comment->CommentID.'/'.$Session->TransientKey().'/?Target='.urlencode("/discussion/{$Comment->DiscussionID}/x"), 'Class' => 'DeleteComment');

   // DEPRECATED (as of 2.1)
   $Sender->EventArguments['Type'] = 'Comment';

   // Allow plugins to add options
   $Sender->EventArguments['CommentOptions'] = &$Options;
   $Sender->EventArguments['Comment'] = $Comment;
   $Sender->FireEvent('CommentOptions');

	return $Options;
}
endif;
/**
 * Output comment options.
 *
 * @since 2.1
 * @param DataSet $Comment.
 */
if (!function_exists('WriteCommentOptions')):
function WriteCommentOptions($Comment) {
	$Controller = Gdn::Controller();
	$Session = Gdn::Session();

   $Id = $Comment->CommentID;
	$Options = GetCommentOptions($Comment);
	if (empty($Options))
		return;

   echo '<span class="ToggleFlyout OptionsMenu">';
      echo '<span class="OptionsTitle" title="'.T('Options').'">'.T('Options').'</span>';
		echo Sprite('SpFlyoutHandle', 'Arrow');
      echo '<ul class="Flyout MenuItems">';
      foreach ($Options as $Code => $Option):
         echo Wrap(Anchor($Option['Label'], $Option['Url'], GetValue('Class', $Option, $Code)), 'li');
      endforeach;
      echo '</ul>';
   echo '</span>';
   if (C('Vanilla.AdminCheckboxes.Use')) {
      // Only show the checkbox if the user has permission to affect multiple items
      $Discussion = Gdn::Controller()->Data('Discussion');
      $PermissionCategoryID = GetValue('PermissionCategoryID', $Discussion);
      if ($Session->CheckPermission('Vanilla.Comments.Delete', TRUE, 'Category', $PermissionCategoryID)) {
         if (!property_exists($Controller, 'CheckedComments'))
            $Controller->CheckedComments = $Session->GetAttribute('CheckedComments', array());

         $ItemSelected = InSubArray($Id, $Controller->CheckedComments);
         echo '<span class="AdminCheck"><input type="checkbox" name="'.'Comment'.'ID[]" value="'.$Id.'"'.($ItemSelected?' checked="checked"':'').' /></span>';
      }
   }
}
endif;

/**
 * Output comment form.
 *
 * @since 2.1
 */
if (!function_exists('WriteCommentForm')):
function WriteCommentForm() {
	$Session = Gdn::Session();
	$Controller = Gdn::Controller();

	$Discussion = $Controller->Data('Discussion');
	$PermissionCategoryID = GetValue('PermissionCategoryID', $Discussion);
	$UserCanClose = $Session->CheckPermission('Vanilla.Discussions.Close', TRUE, 'Category', $PermissionCategoryID);
	$UserCanComment = $Session->CheckPermission('Vanilla.Comments.Add', TRUE, 'Category', $PermissionCategoryID);

	// Closed notification
	if ($Discussion->Closed == '1') {
		?>
		<div class="Foot Closed">
			<div class="Note Closed"><?php echo T('This discussion has been closed.'); ?></div>
			<?php //echo Anchor(T('All Discussions'), 'discussions', 'TabLink'); ?>
		</div>
		<?php
	} else if (!$UserCanComment) {
      if (!Gdn::Session()->IsValid()) {
		?>
		<div class="Foot Closed">
			<div class="Note Closed SignInOrRegister"><?php
			   $Popup =  (C('Garden.SignIn.Popup')) ? ' class="Popup"' : '';
            echo FormatString(
               T('Sign In or Register to Comment.', '<a href="{SignInUrl,html}"{Popup}>Sign In</a> or <a href="{RegisterUrl,html}">Register</a> to comment.'),
               array(
                  'SignInUrl' => Url(SignInUrl(Url(''))),
                  'RegisterUrl' => Url(RegisterUrl(Url(''))),
                  'Popup' => $Popup
               )
            ); ?>
         </div>
			<?php //echo Anchor(T('All Discussions'), 'discussions', 'TabLink'); ?>
		</div>
		<?php
      }
	}

	if (($Discussion->Closed == '1' && $UserCanClose) || ($Discussion->Closed == '0' && $UserCanComment))
		echo $Controller->FetchView('comment', 'post');
}
endif;

if (!function_exists('WriteCommentFormHeader')):
function WriteCommentFormHeader() {
   $Session = Gdn::Session();
   if (C('Vanilla.Comment.UserPhotoFirst', TRUE)) {
      echo UserPhoto($Session->User);
      echo UserAnchor($Session->User, 'Username');
   } else {
      echo UserAnchor($Session->User, 'Username');
      echo UserPhoto($Session->User);
   }
}
endif;

if (!function_exists('WriteEmbedCommentForm')):
function WriteEmbedCommentForm() {
 	$Session = Gdn::Session();
	$Controller = Gdn::Controller();
	$Discussion = $Controller->Data('Discussion');

   if ($Discussion && $Discussion->Closed == '1') {
   ?>
   <div class="Foot Closed">
      <div class="Note Closed"><?php echo T('This discussion has been closed.'); ?></div>
   </div>
   <?php } else { ?>
   <h2><?php echo T('Leave a comment'); ?></h2>
   <div class="MessageForm CommentForm EmbedCommentForm">
      <?php
      echo $Controller->Form->Open(array('id' => 'Form_Comment'));
      echo $Controller->Form->Errors();
      echo $Controller->Form->Hidden('Name');
      echo Wrap($Controller->Form->TextBox('Body', array('MultiLine' => TRUE)), 'div', array('class' => 'TextBoxWrapper'));
      echo "<div class=\"Buttons\">\n";

      $AllowSigninPopup = C('Garden.SignIn.Popup');
      $Attributes = array('tabindex' => '-1', 'target' => '_top');

      // If we aren't ajaxing this call then we need to target the url of the parent frame.
      $ReturnUrl = $Controller->Data('ForeignSource.vanilla_url', Gdn::Request()->PathAndQuery());
      
      if ($Session->IsValid()) {
         $AuthenticationUrl = Gdn::Authenticator()->SignOutUrl($ReturnUrl);
         echo Wrap(
            sprintf(
               T('Commenting as %1$s (%2$s)', 'Commenting as %1$s <span class="SignOutWrap">(%2$s)</span>'),
               Gdn_Format::Text($Session->User->Name),
               Anchor(T('Sign Out'), $AuthenticationUrl, 'SignOut', $Attributes)
            ),
            'div',
            array('class' => 'Author')
         );
         echo $Controller->Form->Button('Post Comment', array('class' => 'Button CommentButton'));
      } else {
         $AuthenticationUrl = SignInUrl($ReturnUrl);
         if ($AllowSigninPopup) {
            $CssClass = 'SignInPopup Button Stash';
         } else {
            $CssClass = 'Button Stash';
         }

         echo Anchor(T('Comment As ...'), $AuthenticationUrl, $CssClass, $Attributes);
      }
      echo "</div>\n";
      echo $Controller->Form->Close();
      ?>
   </div>
   <?php
   }
}
endif;

if (!function_exists('IsMeAction')):
   function IsMeAction($Row) {
      if (!C('Garden.Format.MeActions'))
         return;
      $Row = (array)$Row;
      if (!array_key_exists('Body', $Row))
         return FALSE;

      return strpos(trim($Row['Body']), '/me ') === 0;
   }
endif;

if (!function_exists('FormatMeAction')):
   function FormatMeAction($Comment) {
      if (!IsMeAction($Comment))
         return;

      // Maxlength (don't let people blow up the forum)
      $Comment->Body = substr($Comment->Body, 4);
      $Maxlength = C('Vanilla.MeAction.MaxLength', 100);
      $Body = FormatBody($Comment);
      if (strlen($Body) > $Maxlength)
         $Body = substr($Body, 0, $Maxlength).'...';

      return '<div class="AuthorAction">'.$Body.'</div>';
   }
endif;