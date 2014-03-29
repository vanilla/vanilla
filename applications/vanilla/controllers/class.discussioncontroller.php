<?php if (!defined('APPLICATION')) exit();

/**
 * Handles accessing & displaying a single discussion.
 *
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 * @since 2.0.0
 * @package Vanilla
 */

class DiscussionController extends VanillaController {
   /**
    * Models to include.
    *
    * @since 2.0.0
    * @access public
    * @var array
    */
   public $Uses = array('DiscussionModel', 'CommentModel', 'Form');

   /**
    * Unique identifier.
    *
    * @since 2.0.0
    * @access public
    * @var array
    */
   public $CategoryID;

   /**
    * @var DiscussionModel
    */
   public $DiscussionModel;


   public function __get($Name) {
      switch ($Name) {
         case 'CommentData':
            Deprecated('DiscussionController->CommentData', "DiscussionController->Data('Comments')");
            return $this->Data('Comments');
            break;
      }
      throw new Exception("DiscussionController->$Name not found.", 400);
   }

   /**
    * Default single discussion display.
    *
    * @since 2.0.0
    * @access public
    *
    * @param int $DiscussionID Unique discussion ID
    * @param string $DiscussionStub URL-safe title slug
    * @param int $Page The current page of comments
    */
   public function Index($DiscussionID = '', $DiscussionStub = '', $Page = '') {
      // Setup head
      $Session = Gdn::Session();
      $this->AddJsFile('jquery.autogrow.js');
      $this->AddJsFile('discussion.js');
      $this->AddJsFile('autosave.js');
      Gdn_Theme::Section('Discussion');

      // Load the discussion record
      $DiscussionID = (is_numeric($DiscussionID) && $DiscussionID > 0) ? $DiscussionID : 0;
      if (!array_key_exists('Discussion', $this->Data))
         $this->SetData('Discussion', $this->DiscussionModel->GetID($DiscussionID), TRUE);

      if(!is_object($this->Discussion)) {
         throw new Exception(sprintf(T('%s Not Found'), T('Discussion')), 404);
      }

      // Define the query offset & limit.
      $Limit = C('Vanilla.Comments.PerPage', 30);

      $OffsetProvided = $Page != '';
      list($Offset, $Limit) = OffsetLimit($Page, $Limit);

      // Check permissions
      $this->Permission('Vanilla.Discussions.View', TRUE, 'Category', $this->Discussion->PermissionCategoryID);
      $this->SetData('CategoryID', $this->CategoryID = $this->Discussion->CategoryID, TRUE);

      if (strcasecmp(GetValue('Type', $this->Discussion), 'redirect') === 0) {
         $this->RedirectDiscussion($this->Discussion);
      }

      $Category = CategoryModel::Categories($this->Discussion->CategoryID);
      $this->SetData('Category', $Category);

      if ($CategoryCssClass = GetValue('CssClass', $Category))
         Gdn_Theme::Section($CategoryCssClass);

      $this->SetData('Breadcrumbs', CategoryModel::GetAncestors($this->CategoryID));

      // Setup
      $this->Title($this->Discussion->Name);

      // Actual number of comments, excluding the discussion itself.
      $ActualResponses = $this->Discussion->CountComments;

      $this->Offset = $Offset;
      if (C('Vanilla.Comments.AutoOffset')) {
//         if ($this->Discussion->CountCommentWatch > 1 && $OffsetProvided == '')
//            $this->AddDefinition('ScrollTo', 'a[name=Item_'.$this->Discussion->CountCommentWatch.']');
         if (!is_numeric($this->Offset) || $this->Offset < 0 || !$OffsetProvided) {
            // Round down to the appropriate offset based on the user's read comments & comments per page
            $CountCommentWatch = $this->Discussion->CountCommentWatch > 0 ? $this->Discussion->CountCommentWatch : 0;
            if ($CountCommentWatch > $ActualResponses)
               $CountCommentWatch = $ActualResponses;

            // (((67 comments / 10 perpage) = 6.7) rounded down = 6) * 10 perpage = offset 60;
            $this->Offset = floor($CountCommentWatch / $Limit) * $Limit;
         }
         if ($ActualResponses <= $Limit)
            $this->Offset = 0;

         if ($this->Offset == $ActualResponses)
            $this->Offset -= $Limit;
      } else {
         if ($this->Offset == '')
            $this->Offset = 0;
      }

      if ($this->Offset < 0)
         $this->Offset = 0;


      $LatestItem = $this->Discussion->CountCommentWatch;
      if ($LatestItem === NULL) {
         $LatestItem = 0;
      } elseif ($LatestItem < $this->Discussion->CountComments) {
         $LatestItem += 1;
      }

      $this->SetData('_LatestItem', $LatestItem);

      // Set the canonical url to have the proper page title.
      $this->CanonicalUrl(DiscussionUrl($this->Discussion, PageNumber($this->Offset, $Limit, 0, FALSE)));

//      Url(ConcatSep('/', 'discussion/'.$this->Discussion->DiscussionID.'/'. Gdn_Format::Url($this->Discussion->Name), PageNumber($this->Offset, $Limit, TRUE, Gdn::Session()->UserID != 0)), TRUE), Gdn::Session()->UserID == 0);

      // Load the comments
      $this->SetData('Comments', $this->CommentModel->Get($DiscussionID, $Limit, $this->Offset));

      $PageNumber = PageNumber($this->Offset, $Limit);
      $this->SetData('Page', $PageNumber);
      $this->_SetOpenGraph();


      include_once(PATH_LIBRARY.'/vendors/simplehtmldom/simple_html_dom.php');
      if ($PageNumber == 1) {
         $this->Description(SliceParagraph(Gdn_Format::PlainText($this->Discussion->Body, $this->Discussion->Format), 160));
         // Add images to head for open graph
         $Dom = str_get_html(Gdn_Format::To($this->Discussion->Body, $this->Discussion->Format));
      } else {
         $this->Data['Title'] .= sprintf(T(' - Page %s'), PageNumber($this->Offset, $Limit));

         $FirstComment = $this->Data('Comments')->FirstRow();
         $FirstBody = GetValue('Body', $FirstComment);
         $FirstFormat = GetValue('Format', $FirstComment);
         $this->Description(SliceParagraph(Gdn_Format::PlainText($FirstBody, $FirstFormat), 160));
         // Add images to head for open graph
         $Dom = str_get_html(Gdn_Format::To($FirstBody, $FirstFormat));
      }

      if ($Dom) {
         foreach($Dom->find('img') as $img) {
            if (isset($img->src))
               $this->Image($img->src);
         }
      }

      // Queue notification.
      if ($this->Request->Get('new') && C('Vanilla.QueueNotifications')) {
         $this->AddDefinition('NotifyNewDiscussion', 1);
      }

      // Make sure to set the user's discussion watch records
      $this->CommentModel->SetWatch($this->Discussion, $Limit, $this->Offset, $this->Discussion->CountComments);

      // Build a pager
      $PagerFactory = new Gdn_PagerFactory();
		$this->EventArguments['PagerType'] = 'Pager';
		$this->FireEvent('BeforeBuildPager');
      $this->Pager = $PagerFactory->GetPager($this->EventArguments['PagerType'], $this);
      $this->Pager->ClientID = 'Pager';

      $this->Pager->Configure(
         $this->Offset,
         $Limit,
         $ActualResponses,
         array('DiscussionUrl')
      );
      $this->Pager->Record = $this->Discussion;
      PagerModule::Current($this->Pager);
      $this->FireEvent('AfterBuildPager');

      // Define the form for the comment input
      $this->Form = Gdn::Factory('Form', 'Comment');
      $this->Form->Action = Url('/vanilla/post/comment/');
      $this->DiscussionID = $this->Discussion->DiscussionID;
      $this->Form->AddHidden('DiscussionID', $this->DiscussionID);
      $this->Form->AddHidden('CommentID', '');

      // Look in the session stash for a comment
      $StashComment = $Session->Stash('CommentForDiscussionID_'.$this->Discussion->DiscussionID, '', FALSE);
      if ($StashComment)
         $this->Form->SetFormValue('Body', $StashComment);

      // Retrieve & apply the draft if there is one:
      if (Gdn::Session()->UserID) {
         $DraftModel = new DraftModel();
         $Draft = $DraftModel->Get($Session->UserID, 0, 1, $this->Discussion->DiscussionID)->FirstRow();
         $this->Form->AddHidden('DraftID', $Draft ? $Draft->DraftID : '');
         if ($Draft && !$this->Form->IsPostBack()) {
            $this->Form->SetValue('Body', $Draft->Body);
            $this->Form->SetValue('Format', $Draft->Format);
         }
      }

      // Deliver JSON data if necessary
      if ($this->_DeliveryType != DELIVERY_TYPE_ALL) {
         $this->SetJson('LessRow', $this->Pager->ToString('less'));
         $this->SetJson('MoreRow', $this->Pager->ToString('more'));
         $this->View = 'comments';
      }

		// Inform moderator of checked comments in this discussion
		$CheckedComments = $Session->GetAttribute('CheckedComments', array());
		if (count($CheckedComments) > 0)
			ModerationController::InformCheckedComments($this);

      // Add modules
      $this->AddModule('DiscussionFilterModule');
      $this->AddModule('NewDiscussionModule');
      $this->AddModule('CategoriesModule');
      $this->AddModule('BookmarkedModule');

		$this->CanEditComments = Gdn::Session()->CheckPermission('Vanilla.Comments.Edit', TRUE, 'Category', 'any') && C('Vanilla.AdminCheckboxes.Use');

      // Report the discussion id so js can use it.
      $this->AddDefinition('DiscussionID', $DiscussionID);

      $this->FireEvent('BeforeDiscussionRender');
      $this->Render();
   }

   /**
    * Display comments in a discussion since a particular CommentID.
    *
    * @since 2.0.0
    * @access public
    *
    * @param int $DiscussionID Unique discussion ID
    * @param int $LastCommentID Only shows comments posted after this one
    */
   public function GetNew($DiscussionID, $LastCommentID = 0) {
      $this->SetData('Discussion', $this->DiscussionModel->GetID($DiscussionID), TRUE);

      // Check permissions.
      $this->Permission('Vanilla.Discussions.View', TRUE, 'Category', $this->Discussion->PermissionCategoryID);
      $this->SetData('CategoryID', $this->CategoryID = $this->Discussion->CategoryID, TRUE);

      // Get the comments.
      $Comments = $this->CommentModel->GetNew($DiscussionID, $LastCommentID)->Result();
      $this->SetData('Comments', $Comments, TRUE);

      // Set the data.
      if(count($Comments) > 0) {
         $LastComment = $Comments[count($Comments) - 1];
         // Mark the comment read.
         $this->SetData('Offset', $this->Discussion->CountComments, TRUE);
         $this->CommentModel->SetWatch($this->Discussion, $this->Discussion->CountComments, $this->Discussion->CountComments, $this->Discussion->CountComments);

         $LastCommentID = $this->Json('LastCommentID');
         if(is_null($LastCommentID) || $LastComment->CommentID > $LastCommentID) {
            $this->Json('LastCommentID', $LastComment->CommentID);
         }
      } else {
         $this->SetData('Offset', $this->CommentModel->GetOffset($LastCommentID), TRUE);
      }

      $this->View = 'comments';
      $this->Render();
   }

   /**
    * Highlight route & add common JS definitions.
    *
    * Always called by dispatcher before controller's requested method.
    *
    * @since 2.0.0
    * @access public
    */
   public function Initialize() {
      parent::Initialize();
      $this->AddDefinition('ImageResized', T('This image has been resized to fit in the page. Click to enlarge.'));
      $this->AddDefinition('ConfirmDeleteCommentHeading', T('ConfirmDeleteCommentHeading', 'Delete Comment'));
      $this->AddDefinition('ConfirmDeleteCommentText', T('ConfirmDeleteCommentText', 'Are you sure you want to delete this comment?'));
      $this->Menu->HighlightRoute('/discussions');
   }

   /**
    * Display discussion page starting with a particular comment.
    *
    * @since 2.0.0
    * @access public
    *
    * @param int $CommentID Unique comment ID
    */
   public function Comment($CommentID) {
      // Get the discussionID
      $Comment = $this->CommentModel->GetID($CommentID);
      if (!$Comment)
         throw NotFoundException('Comment');

      $DiscussionID = $Comment->DiscussionID;

      // Figure out how many comments are before this one
      $Offset = $this->CommentModel->GetOffset($Comment);
      $Limit = Gdn::Config('Vanilla.Comments.PerPage', 30);

      $PageNumber = PageNumber($Offset, $Limit, TRUE);
      $this->SetData('Page', $PageNumber);

      $this->View = 'index';
      $this->Index($DiscussionID, 'x', $PageNumber);
   }

   /**
    * Allows user to remove announcement.
    *
    * Users may remove announcements from being displayed for themselves only.
    * Does not affect what announcements are shown for other users.
    *
    * @since 2.0.0
    * @access public
    *
    * @param int $DiscussionID Unique discussion ID.
    * @param string $TransientKey Single-use hash to prove intent.
    */
   public function DismissAnnouncement($DiscussionID = '') {
      // Confirm announcements may be dismissed
      if (!C('Vanilla.Discussions.Dismiss', 1)) {
         throw PermissionException('Vanilla.Discussions.Dismiss');
      }

      // Make sure we are posting back.
      if (!$this->Request->IsPostBack())
         throw PermissionException('Javascript');

      $Session = Gdn::Session();
      if (
         is_numeric($DiscussionID)
         && $DiscussionID > 0
         && $Session->UserID > 0
      ) $this->DiscussionModel->DismissAnnouncement($DiscussionID, $Session->UserID);

      // Redirect back where the user came from if necessary
      if ($this->_DeliveryType === DELIVERY_TYPE_ALL)
         SafeRedirect('discussions');

      $this->JsonTarget("#Discussion_$DiscussionID", NULL, 'SlideUp');

      $this->Render('Blank', 'Utility', 'Dashboard');
   }

   /**
    * Allows user to bookmark or unbookmark a discussion.
    *
    * If the discussion isn't bookmarked by the user, this bookmarks it.
    * If it is already bookmarked, this unbookmarks it.
    *
    * @since 2.0.0
    * @access public
    *
    * @param int $DiscussionID Unique discussion ID.
    */
   public function Bookmark($DiscussionID = NULL) {
      // Make sure we are posting back.
      if (!$this->Request->IsPostBack())
         throw PermissionException('Javascript');

      $Session = Gdn::Session();

      if (!$Session->UserID)
         throw PermissionException('SignedIn');

      // Check the form to see if the data was posted.
      $Form = new Gdn_Form();
      $DiscussionID = $Form->GetFormValue('DiscussionID', $DiscussionID);
      $Bookmark = $Form->GetFormValue('Bookmark', NULL);
      $UserID = $Form->GetFormValue('UserID', $Session->UserID);

      // Check the permission on the user.
      if ($UserID != $Session->UserID) {
         $this->Permission('Garden.Moderation.Manage');
      }

      $Discussion = $this->DiscussionModel->GetID($DiscussionID);
      if (!$Discussion)
         throw NotFoundException('Discussion');

      $Bookmark = $this->DiscussionModel->Bookmark($DiscussionID, $UserID, $Bookmark);

      // Set the new value for api calls and json targets.
      $this->SetData(array(
         'UserID' => $UserID,
         'DiscussionID' => $DiscussionID,
         'Bookmarked' => (bool)$Bookmark
         ));
      SetValue('Bookmarked', $Discussion, (int)$Bookmark);

      // Update the user's bookmark count
      $CountBookmarks = $this->DiscussionModel->SetUserBookmarkCount($UserID);
      $this->JsonTarget('.User-CountBookmarks', (string)$CountBookmarks);

      //  Short circuit if this is an api call.
      if ($this->DeliveryType() === DELIVERY_TYPE_DATA) {
         $this->Render('Blank', 'Utility', 'Dashboard');
         return;
      }

      // Return the appropriate bookmark.
      require_once $this->FetchViewLocation('helper_functions', 'Discussions');
      $Html = BookmarkButton($Discussion);
//      $this->JsonTarget(".Section-DiscussionList #Discussion_$DiscussionID .Bookmark,.Section-Discussion .PageTitle .Bookmark", $Html, 'ReplaceWith');
      $this->JsonTarget("!element", $Html, 'ReplaceWith');

      // Add the bookmark to the bookmarks module.
      if($Bookmark) {
         // Grab the individual bookmark and send it to the client.
         $Bookmarks = new BookmarkedModule($this);
         if($CountBookmarks == 1) {
            // When there is only one bookmark we have to get the whole module.
            $Target = '#Panel';
            $Type = 'Append';
            $Bookmarks->GetData();
            $Data = $Bookmarks->ToString();
         } else {
            $Target = '#Bookmark_List';
            $Type = 'Prepend';
            $Loc = $Bookmarks->FetchViewLocation('discussion');

            ob_start();
            include($Loc);
            $Data = ob_get_clean();
         }

         $this->JsonTarget($Target, $Data, $Type);
      } else {
         // Send command to remove bookmark html.
         if($CountBookmarks == 0) {
            $this->JsonTarget('#Bookmarks', NULL, 'Remove');
         } else {
            $this->JsonTarget('#Bookmark_'.$DiscussionID, NULL, 'Remove');
         }
      }

      $this->Render('Blank', 'Utility', 'Dashboard');
   }

   /**
    * Allows user to announce or unannounce a discussion.
    *
    * If the discussion isn't announced, this announces it.
    * If it is already announced, this unannounces it.
    * Announced discussions stay at the top of the discussions
    * list regardless of how long ago the last comment was.
    *
    * @since 2.0.0
    * @access public
    *
    * @param int $DiscussionID Unique discussion ID.
    * @param string $TransientKey Single-use hash to prove intent.
    */
   public function Announce($DiscussionID = '', $Target = '') {
      $Discussion = $this->DiscussionModel->GetID($DiscussionID);
      if (!$Discussion)
         throw NotFoundException('Discussion');
      $this->Permission('Vanilla.Discussions.Announce', TRUE, 'Category', $Discussion->PermissionCategoryID);

      if ($this->Form->IsPostBack()) {
         // Save the property.
         $CacheKeys = array('Announcements', 'Announcements_'.GetValue('CategoryID', $Discussion));
         $this->DiscussionModel->SQL->Cache($CacheKeys);
         $this->DiscussionModel->SetProperty($DiscussionID, 'Announce', (int)$this->Form->GetFormValue('Announce', 0));

         if ($Target)
            $this->RedirectUrl = Url($Target);
      } else {
         if (!$Discussion->Announce)
            $Discussion->Announce = 2;
         $this->Form->SetData($Discussion);
      }

      $Discussion = (array)$Discussion;
      $Category = CategoryModel::Categories($Discussion['CategoryID']);

      $this->SetData('Discussion', $Discussion);
      $this->SetData('Category', $Category);

      $this->Title(T('Announce'));
      $this->Render();
   }

   public function SendOptions($Discussion) {
      require_once $this->FetchViewLocation('helper_functions', 'Discussion');
      ob_start();
      WriteDiscussionOptions($Discussion);
      $Options = ob_get_clean();

      $this->JsonTarget("#Discussion_{$Discussion->DiscussionID} .OptionsMenu,.Section-Discussion .Discussion .OptionsMenu", $Options, 'ReplaceWith');
   }

   /**
    * Allows user to sink or unsink a discussion.
    *
    * If the discussion isn't sunk, this sinks it. If it is already sunk,
    * this unsinks it. Sunk discussions do not move to the top of the
    * discussion list when a new comment is added.
    *
    * @since 2.0.0
    * @access public
    *
    * @param int $DiscussionID Unique discussion ID.
    * @param bool $Sink Whether or not to unsink the discussion.
    */
   public function Sink($DiscussionID = '', $Sink = TRUE, $From = 'list') {
      // Make sure we are posting back.
      if (!$this->Request->IsPostBack())
         throw PermissionException('Javascript');

      $Discussion = $this->DiscussionModel->GetID($DiscussionID);

      if (!$Discussion)
         throw NotFoundException('Discussion');

      $this->Permission('Vanilla.Discussions.Sink', TRUE, 'Category', $Discussion->PermissionCategoryID);

      // Sink the discussion.
      $this->DiscussionModel->SetField($DiscussionID, 'Sink', $Sink);
      $Discussion->Sink = $Sink;

      // Redirect to the front page
      if ($this->_DeliveryType === DELIVERY_TYPE_ALL) {
         $Target = GetIncomingValue('Target', 'discussions');
         SafeRedirect($Target);
      }

      $this->SendOptions($Discussion);

      $this->JsonTarget("#Discussion_$DiscussionID", NULL, 'Highlight');
      $this->JsonTarget(".Discussion #Item_0", NULL, 'Highlight');

      $this->Render('Blank', 'Utility', 'Dashboard');
   }

   /**
    * Allows user to close or re-open a discussion.
    *
    * If the discussion isn't closed, this closes it. If it is already
    * closed, this re-opens it. Closed discussions may not have new
    * comments added to them.
    *
    * @since 2.0.0
    * @access public
    *
    * @param int $DiscussionID Unique discussion ID.
    * @param bool $Close Whether or not to close the discussion.
    */
   public function Close($DiscussionID = '', $Close = TRUE, $From = 'list') {
      // Make sure we are posting back.
      if (!$this->Request->IsPostBack())
         throw PermissionException('Javascript');

      $Discussion = $this->DiscussionModel->GetID($DiscussionID);

      if (!$Discussion)
         throw NotFoundException('Discussion');

      $this->Permission('Vanilla.Discussions.Close', TRUE, 'Category', $Discussion->PermissionCategoryID);

      // Close the discussion.
      $this->DiscussionModel->SetField($DiscussionID, 'Closed', $Close);
      $Discussion->Closed = $Close;

      // Redirect to the front page
      if ($this->_DeliveryType === DELIVERY_TYPE_ALL) {
         $Target = GetIncomingValue('Target', 'discussions');
         SafeRedirect($Target);
      }

      $this->SendOptions($Discussion);

      if ($Close) {
         require_once $this->FetchViewLocation('helper_functions', 'Discussions');
         $this->JsonTarget(".Section-DiscussionList #Discussion_$DiscussionID .Meta-Discussion", Tag($Discussion, 'Closed', 'Closed'), 'Prepend');
         $this->JsonTarget(".Section-DiscussionList #Discussion_$DiscussionID", 'Closed', 'AddClass');
      } else {
         $this->JsonTarget(".Section-DiscussionList #Discussion_$DiscussionID .Tag-Closed", NULL, 'Remove');
         $this->JsonTarget(".Section-DiscussionList #Discussion_$DiscussionID", 'Closed', 'RemoveClass');
      }

      $this->JsonTarget("#Discussion_$DiscussionID", NULL, 'Highlight');
      $this->JsonTarget(".Discussion #Item_0", NULL, 'Highlight');

      $this->Render('Blank', 'Utility', 'Dashboard');
   }

   /**
    * Allows user to delete a discussion.
    *
    * This is a "hard" delete - it is removed from the database.
    *
    * @since 2.0.0
    * @access public
    *
    * @param int $DiscussionID Unique discussion ID.
    */
   public function Delete($DiscussionID, $Target = '') {
      $Discussion = $this->DiscussionModel->GetID($DiscussionID);

      if (!$Discussion)
         throw NotFoundException('Discussion');

      $this->Permission('Vanilla.Discussions.Delete', TRUE, 'Category', $Discussion->PermissionCategoryID);

      if ($this->Form->IsPostBack()) {
         if (!$this->DiscussionModel->Delete($DiscussionID))
            $this->Form->AddError('Failed to delete discussion');

         if ($this->Form->ErrorCount() == 0) {
            if ($this->_DeliveryType === DELIVERY_TYPE_ALL)
               SafeRedirect($Target);

            if ($Target)
               $this->RedirectUrl = Url($Target);

            $this->JsonTarget(".Section-DiscussionList #Discussion_$DiscussionID", NULL, 'SlideUp');
         }
      }

      $this->SetData('Title', T('Delete Discussion'));
      $this->Render();
   }

   /**
    * Allows user to delete a comment.
    *
    * If the comment is the only one in the discussion, the discussion will
    * be deleted as well. Users without administrative delete abilities
    * should not be able to delete a comment unless it is a draft. This is
    * a "hard" delete - it is removed from the database.
    *
    * @since 2.0.0
    * @access public
    *
    * @param int $CommentID Unique comment ID.
    * @param string $TransientKey Single-use hash to prove intent.
    */
   public function DeleteComment($CommentID = '', $TransientKey = '') {
      $Session = Gdn::Session();
      $DefaultTarget = '/discussions/';
      $ValidCommentID = is_numeric($CommentID) && $CommentID > 0;
      $ValidUser = $Session->UserID > 0 && $Session->ValidateTransientKey($TransientKey);

      if ($ValidCommentID && $ValidUser) {
         // Get comment and discussion data
         $Comment = $this->CommentModel->GetID($CommentID);
         $DiscussionID = GetValue('DiscussionID', $Comment);
         $Discussion = $this->DiscussionModel->GetID($DiscussionID);

         if ($Comment && $Discussion) {
            $DefaultTarget = DiscussionUrl($Discussion);

            // Make sure comment is this user's or they have Delete permission
            if ($Comment->InsertUserID != $Session->UserID || !C('Vanilla.Comments.AllowSelfDelete'))
               $this->Permission('Vanilla.Comments.Delete', TRUE, 'Category', $Discussion->PermissionCategoryID);

            // Make sure that content can (still) be edited
            $EditContentTimeout = C('Garden.EditContentTimeout', -1);
            $CanEdit = $EditContentTimeout == -1 || strtotime($Comment->DateInserted) + $EditContentTimeout > time();
            if (!$CanEdit)
               $this->Permission('Vanilla.Comments.Delete', TRUE, 'Category', $Discussion->PermissionCategoryID);

            // Delete the comment
            if (!$this->CommentModel->Delete($CommentID))
               $this->Form->AddError('Failed to delete comment');
         }
         else {
            $this->Form->AddError('Invalid comment');
         }
      } else {
         $this->Form->AddError('ErrPermission');
      }

      // Redirect
      if ($this->_DeliveryType == DELIVERY_TYPE_ALL) {
         $Target = GetIncomingValue('Target', $DefaultTarget);
         SafeRedirect($Target);
      }

      if ($this->Form->ErrorCount() > 0) {
         $this->SetJson('ErrorMessage', $this->Form->Errors());
      } else {
         $this->JsonTarget("#Comment_$CommentID", '', 'SlideUp');
      }

      $this->Render();
   }

   /**
    * Alternate version of Index that uses the embed master view.
    */
   public function Embed($DiscussionID = '', $DiscussionStub = '', $Offset = '', $Limit = '') {
      $this->Title(T('Comments'));
      $this->AddDefinition('DoInform', '0'); // Suppress inform messages on embedded page.
      $this->AddDefinition('SelfUrl', Gdn::Request()->PathAndQuery());
      $this->AddDefinition('Embedded', TRUE);
      $this->CanEditComments = FALSE; // Don't show the comment checkboxes on the embed comments page
      $this->Theme = C('Garden.CommentsTheme', $this->Theme);
      Gdn_Theme::Section('Comments');

      // Add some css to help with the transparent bg on embedded comments
      if ($this->Head)
         $this->Head->AddString('<style type="text/css">
body { background: transparent !important; }
</style>');
      $Session = Gdn::Session();
      $this->AddJsFile('jquery.gardenmorepager.js');
      $this->AddJsFile('jquery.autogrow.js');
      $this->AddJsFile('discussion.js');
      $this->RemoveJsFile('autosave.js');
      $this->MasterView = 'empty';

      // Define incoming variables (prefer querystring parameters over method parameters)
      $DiscussionID = (is_numeric($DiscussionID) && $DiscussionID > 0) ? $DiscussionID : 0;
      $DiscussionID = GetIncomingValue('vanilla_discussion_id', $DiscussionID);
      $Offset = GetIncomingValue('Offset', $Offset);
      $Limit = GetIncomingValue('Limit', $Limit);
      $vanilla_identifier = GetIncomingValue('vanilla_identifier', '');
      // Only allow vanilla identifiers of 32 chars or less - md5 if larger
      if (strlen($vanilla_identifier) > 32) {
         $vanilla_identifier = md5($vanilla_identifier);
      }
      $vanilla_type = GetIncomingValue('vanilla_type', 'page');
      $vanilla_url = GetIncomingValue('vanilla_url', '');
      $vanilla_category_id = GetIncomingValue('vanilla_category_id', '');
      $ForeignSource = array(
         'vanilla_identifier' => $vanilla_identifier,
         'vanilla_type' => $vanilla_type,
         'vanilla_url' => $vanilla_url,
         'vanilla_category_id' => $vanilla_category_id
      );
      $this->SetData('ForeignSource', $ForeignSource);

      $SortComments = C('Garden.Embed.SortComments') == 'desc' ? 'desc' : 'asc';
      $this->SetData('SortComments', $SortComments);

      // Retrieve the discussion record.
      $Discussion = FALSE;
      if ($DiscussionID > 0) {
         $Discussion = $this->DiscussionModel->GetID($DiscussionID);
      } else if ($vanilla_identifier != '' && $vanilla_type != '') {
         $Discussion = $this->DiscussionModel->GetForeignID($vanilla_identifier, $vanilla_type);
      }

      if ($Discussion) {
         $this->SetData('Discussion', $Discussion, TRUE);
         $this->SetData('DiscussionID', $Discussion->DiscussionID, TRUE);
         $this->Title($this->Discussion->Name);

         // Actual number of comments, excluding the discussion itself
         $ActualResponses = $this->Discussion->CountComments;
         // Define the query offset & limit
         if (!is_numeric($Limit) || $Limit < 0)
            $Limit = C('Garden.Embed.CommentsPerPage', 30);

         $OffsetProvided = $Offset != '';
         list($Offset, $Limit) = OffsetLimit($Offset, $Limit);
         $this->Offset = $Offset;
         if (C('Vanilla.Comments.AutoOffset')) {
            if ($ActualResponses <= $Limit)
               $this->Offset = 0;

            if ($this->Offset == $ActualResponses)
               $this->Offset -= $Limit;
         } else if ($this->Offset == '')
            $this->Offset = 0;

         if ($this->Offset < 0)
            $this->Offset = 0;

         // Set the canonical url to have the proper page title.
         $this->CanonicalUrl(DiscussionUrl($Discussion, PageNumber($this->Offset, $Limit)));

         // Load the comments.
         $CurrentOrderBy = $this->CommentModel->OrderBy();
         if (StringBeginsWith(GetValueR('0.0', $CurrentOrderBy), 'c.DateInserted'))
            $this->CommentModel->OrderBy('c.DateInserted '.$SortComments); // allow custom sort

         $this->SetData('Comments', $this->CommentModel->Get($this->Discussion->DiscussionID, $Limit, $this->Offset), TRUE);

         if (count($this->CommentModel->Where()) > 0)
            $ActualResponses = FALSE;

         $this->SetData('_Count', $ActualResponses);

         // Build a pager
         $PagerFactory = new Gdn_PagerFactory();
         $this->EventArguments['PagerType'] = 'MorePager';
         $this->FireEvent('BeforeBuildPager');
         $this->Pager = $PagerFactory->GetPager($this->EventArguments['PagerType'], $this);
         $this->Pager->ClientID = 'Pager';
         $this->Pager->MoreCode = 'More Comments';
         $this->Pager->Configure(
            $this->Offset,
            $Limit,
            $ActualResponses,
            'discussion/embed/'.$this->Discussion->DiscussionID.'/'.Gdn_Format::Url($this->Discussion->Name).'/%1$s'
         );
         $this->Pager->CurrentRecords = $this->Comments->NumRows();
         $this->FireEvent('AfterBuildPager');
      }

      // Define the form for the comment input
      $this->Form = Gdn::Factory('Form', 'Comment');
      $this->Form->Action = Url('/vanilla/post/comment/');
      $this->Form->AddHidden('CommentID', '');
      $this->Form->AddHidden('Embedded', 'true'); // Tell the post controller that this is an embedded page (in case there are custom views it needs to pick up from a theme).
      $this->Form->AddHidden('DisplayNewCommentOnly', 'true'); // Only load/display the new comment after posting (don't load all new comments since the page last loaded).

      if ($this->Request->Get('title')) {
         $this->Form->SetValue('Name', $this->Request->Get('title'));
      }

      if ($Discussion) {
         $this->Form->AddHidden('DiscussionID', $Discussion->DiscussionID);
      }

      foreach ($ForeignSource as $Key => $Val) {
         // Drop the foreign source information into the form so it can be used if creating a discussion
         $this->Form->AddHidden($Key, $Val);
         // Also drop it into the definitions so it can be picked up for stashing comments
         $this->AddDefinition($Key, $Val);
      }

      // Retrieve & apply the draft if there is one:
      $Draft = FALSE;
      if (Gdn::Session()->UserID && $Discussion) {
         $DraftModel = new DraftModel();
         $Draft = $DraftModel->Get($Session->UserID, 0, 1, $Discussion->DiscussionID)->FirstRow();
         $this->Form->AddHidden('DraftID', $Draft ? $Draft->DraftID : '');
      }

      if ($Draft) {
         $this->Form->SetFormValue('Body', $Draft->Body);
      } else {
         // Look in the session stash for a comment
         $StashComment = $Session->Stash('CommentForForeignID_'.$ForeignSource['vanilla_identifier'], '', FALSE);
         if ($StashComment) {
            $this->Form->SetValue('Body', $StashComment);
            $this->Form->SetFormValue('Body', $StashComment);
         }
      }

      // Deliver JSON data if necessary
      if ($this->_DeliveryType != DELIVERY_TYPE_ALL) {
         if ($this->Discussion) {
            $this->SetJson('LessRow', $this->Pager->ToString('less'));
            $this->SetJson('MoreRow', $this->Pager->ToString('more'));
         }
         $this->View = 'comments';
      }

      if ($SortComments == 'desc')
         $this->AddDefinition('PrependNewComments', '1');

      // Report the discussion id so js can use it.
      if ($Discussion)
         $this->AddDefinition('DiscussionID', $Discussion->DiscussionID);

      $this->FireEvent('BeforeDiscussionRender');
      $this->Render();
   }

   /**
    * Redirect to the url specified by the discussion.
    * @param array|object $Discussion
    */
   protected function RedirectDiscussion($Discussion) {
      $Body = Gdn_Format::To(GetValue('Body', $Discussion), GetValue('Format', $Discussion));
      if (preg_match('`href="([^"]+)"`i', $Body, $Matches)) {
         $Url = $Matches[1];
         SafeRedirect($Url, 301);
      }
   }

   /**
    * Re-fetch a discussion's content based on its foreign url.
    * @param type $DiscussionID
    */
   public function RefetchPageInfo($DiscussionID) {
      // Make sure we are posting back.
      if (!$this->Request->IsPostBack())
         throw PermissionException('Javascript');

      // Grab the discussion.
      $Discussion = $this->DiscussionModel->GetID($DiscussionID);

      if (!$Discussion)
         throw NotFoundException('Discussion');

      // Make sure the user has permission to edit this discussion.
      $this->Permission('Vanilla.Discussions.Edit', TRUE, 'Category', $Discussion->PermissionCategoryID);

      $ForeignUrl = GetValueR('Attributes.ForeignUrl', $Discussion);
      if (!$ForeignUrl) {
         throw new Gdn_UserException(T("This discussion isn't associated with a url."));
      }

      $Stub = $this->DiscussionModel->FetchPageInfo($ForeignUrl, TRUE);
//      decho($Stub);
//      die();

      // Save the stub.
      $this->DiscussionModel->SetField($DiscussionID, (array)$Stub);

      // Send some of the stuff back.
      if (isset($Stub['Name']))
         $this->JsonTarget('.PageTitle h1', Gdn_Format::Text($Stub['Name']));
      if (isset($Stub['Body']))
         $this->JsonTarget("#Discussion_$DiscussionID .Message", Gdn_Format::To($Stub['Body'], $Stub['Format']));

      $this->InformMessage('The page was successfully fetched.');

      $this->Render('Blank', 'Utility', 'Dashboard');
   }

   protected function _SetOpenGraph() {
      if (!$this->Head)
         return;
      $this->Head->AddTag('meta', array('property' => 'og:type', 'content' => 'article'));
   }
}
