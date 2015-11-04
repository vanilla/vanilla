<?php
/**
 * Discussion controller
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Vanilla
 * @since 2.0
 */

/**
 * Handles accessing & displaying a single discussion via /discussion endpoint.
 */
class DiscussionController extends VanillaController {

    /** @var array Models to include. */
    public $Uses = array('DiscussionModel', 'CommentModel', 'Form');

    /** @var array Unique identifier. */
    public $CategoryID;

    /** @var DiscussionModel */
    public $DiscussionModel;

    /**
     *
     *
     * @param $Name
     * @return mixed
     * @throws Exception
     */
    public function __get($Name) {
        switch ($Name) {
            case 'CommentData':
                Deprecated('DiscussionController->CommentData', "DiscussionController->data('Comments')");
                return $this->data('Comments');
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
    public function index($DiscussionID = '', $DiscussionStub = '', $Page = '') {
        // Setup head
        $Session = Gdn::session();
        $this->addJsFile('jquery.autosize.min.js');
        $this->addJsFile('autosave.js');
        $this->addJsFile('discussion.js');
        Gdn_Theme::section('Discussion');

        // Load the discussion record
        $DiscussionID = (is_numeric($DiscussionID) && $DiscussionID > 0) ? $DiscussionID : 0;
        if (!array_key_exists('Discussion', $this->Data)) {
            $this->setData('Discussion', $this->DiscussionModel->getID($DiscussionID), true);
        }

        if (!is_object($this->Discussion)) {
            throw notFoundException('Discussion');
        }

        // Define the query offset & limit.
        $Limit = c('Vanilla.Comments.PerPage', 30);

        $OffsetProvided = $Page != '';
        list($Offset, $Limit) = offsetLimit($Page, $Limit);

        // Check permissions
        $this->permission('Vanilla.Discussions.View', true, 'Category', $this->Discussion->PermissionCategoryID);
        $this->setData('CategoryID', $this->CategoryID = $this->Discussion->CategoryID, true);

        if (strcasecmp(val('Type', $this->Discussion), 'redirect') === 0) {
            $this->redirectDiscussion($this->Discussion);
        }

        $Category = CategoryModel::categories($this->Discussion->CategoryID);
        $this->setData('Category', $Category);

        if ($CategoryCssClass = val('CssClass', $Category)) {
            Gdn_Theme::section($CategoryCssClass);
        }

        $this->setData('Breadcrumbs', CategoryModel::getAncestors($this->CategoryID));

        // Setup
        $this->title($this->Discussion->Name);

        // Actual number of comments, excluding the discussion itself.
        $ActualResponses = $this->Discussion->CountComments;

        $this->Offset = $Offset;
        if (c('Vanilla.Comments.AutoOffset')) {
//         if ($this->Discussion->CountCommentWatch > 1 && $OffsetProvided == '')
//            $this->addDefinition('ScrollTo', 'a[name=Item_'.$this->Discussion->CountCommentWatch.']');
            if (!is_numeric($this->Offset) || $this->Offset < 0 || !$OffsetProvided) {
                // Round down to the appropriate offset based on the user's read comments & comments per page
                $CountCommentWatch = $this->Discussion->CountCommentWatch > 0 ? $this->Discussion->CountCommentWatch : 0;
                if ($CountCommentWatch > $ActualResponses) {
                    $CountCommentWatch = $ActualResponses;
                }

                // (((67 comments / 10 perpage) = 6.7) rounded down = 6) * 10 perpage = offset 60;
                $this->Offset = floor($CountCommentWatch / $Limit) * $Limit;
            }
            if ($ActualResponses <= $Limit) {
                $this->Offset = 0;
            }

            if ($this->Offset == $ActualResponses) {
                $this->Offset -= $Limit;
            }
        } else {
            if ($this->Offset == '') {
                $this->Offset = 0;
            }
        }

        if ($this->Offset < 0) {
            $this->Offset = 0;
        }


        $LatestItem = $this->Discussion->CountCommentWatch;
        if ($LatestItem === null) {
            $LatestItem = 0;
        } elseif ($LatestItem < $this->Discussion->CountComments) {
            $LatestItem += 1;
        }

        $this->setData('_LatestItem', $LatestItem);

        // Set the canonical url to have the proper page title.
        $this->canonicalUrl(discussionUrl($this->Discussion, pageNumber($this->Offset, $Limit, 0, false)));

//      url(ConcatSep('/', 'discussion/'.$this->Discussion->DiscussionID.'/'. Gdn_Format::url($this->Discussion->Name), PageNumber($this->Offset, $Limit, TRUE, Gdn::session()->UserID != 0)), true), Gdn::session()->UserID == 0);

        // Load the comments
        $this->setData('Comments', $this->CommentModel->get($DiscussionID, $Limit, $this->Offset));

        $PageNumber = PageNumber($this->Offset, $Limit);
        $this->setData('Page', $PageNumber);
        $this->_SetOpenGraph();


        include_once(PATH_LIBRARY.'/vendors/simplehtmldom/simple_html_dom.php');
        if ($PageNumber == 1) {
            $this->description(sliceParagraph(Gdn_Format::plainText($this->Discussion->Body, $this->Discussion->Format), 160));
            // Add images to head for open graph
            $Dom = str_get_html(Gdn_Format::to($this->Discussion->Body, $this->Discussion->Format));
        } else {
            $this->Data['Title'] .= sprintf(t(' - Page %s'), PageNumber($this->Offset, $Limit));

            $FirstComment = $this->data('Comments')->firstRow();
            $FirstBody = val('Body', $FirstComment);
            $FirstFormat = val('Format', $FirstComment);
            $this->description(sliceParagraph(Gdn_Format::plainText($FirstBody, $FirstFormat), 160));
            // Add images to head for open graph
            $Dom = str_get_html(Gdn_Format::to($FirstBody, $FirstFormat));
        }

        if ($Dom) {
            foreach ($Dom->find('img') as $img) {
                if (isset($img->src)) {
                    $this->image($img->src);
                }
            }
        }

        // Queue notification.
        if ($this->Request->get('new') && c('Vanilla.QueueNotifications')) {
            $this->addDefinition('NotifyNewDiscussion', 1);
        }

        // Make sure to set the user's discussion watch records
        $this->CommentModel->SetWatch($this->Discussion, $Limit, $this->Offset, $this->Discussion->CountComments);

        // Build a pager
        $PagerFactory = new Gdn_PagerFactory();
        $this->EventArguments['PagerType'] = 'Pager';
        $this->fireEvent('BeforeBuildPager');
        $this->Pager = $PagerFactory->getPager($this->EventArguments['PagerType'], $this);
        $this->Pager->ClientID = 'Pager';

        $this->Pager->configure(
            $this->Offset,
            $Limit,
            $ActualResponses,
            array('DiscussionUrl')
        );
        $this->Pager->Record = $this->Discussion;
        PagerModule::current($this->Pager);
        $this->fireEvent('AfterBuildPager');

        // Define the form for the comment input
        $this->Form = Gdn::Factory('Form', 'Comment');
        $this->Form->Action = url('/post/comment/');
        $this->DiscussionID = $this->Discussion->DiscussionID;
        $this->Form->addHidden('DiscussionID', $this->DiscussionID);
        $this->Form->addHidden('CommentID', '');

        // Look in the session stash for a comment
        $StashComment = $Session->getPublicStash('CommentForDiscussionID_'.$this->Discussion->DiscussionID);
        if ($StashComment) {
            $this->Form->setFormValue('Body', $StashComment);
        }

        // Retrieve & apply the draft if there is one:
        if (Gdn::session()->UserID) {
            $DraftModel = new DraftModel();
            $Draft = $DraftModel->get($Session->UserID, 0, 1, $this->Discussion->DiscussionID)->firstRow();
            $this->Form->addHidden('DraftID', $Draft ? $Draft->DraftID : '');
            if ($Draft && !$this->Form->isPostBack()) {
                $this->Form->setValue('Body', $Draft->Body);
                $this->Form->setValue('Format', $Draft->Format);
            }
        }

        // Deliver JSON data if necessary
        if ($this->_DeliveryType != DELIVERY_TYPE_ALL) {
            $this->setJson('LessRow', $this->Pager->toString('less'));
            $this->setJson('MoreRow', $this->Pager->toString('more'));
            $this->View = 'comments';
        }

        // Inform moderator of checked comments in this discussion
        $CheckedComments = $Session->getAttribute('CheckedComments', array());
        if (count($CheckedComments) > 0) {
            ModerationController::informCheckedComments($this);
        }

        // Add modules
        $this->addModule('DiscussionFilterModule');
        $this->addModule('NewDiscussionModule');
        $this->addModule('CategoriesModule');
        $this->addModule('BookmarkedModule');

        $this->CanEditComments = Gdn::session()->checkPermission('Vanilla.Comments.Edit', true, 'Category', 'any') && c('Vanilla.AdminCheckboxes.Use');

        // Report the discussion id so js can use it.
        $this->addDefinition('DiscussionID', $DiscussionID);
        $this->addDefinition('Category', $this->data('Category.Name'));

        $this->fireEvent('BeforeDiscussionRender');

        $AttachmentModel = AttachmentModel::instance();
        if (AttachmentModel::enabled()) {
            $AttachmentModel->joinAttachments($this->Data['Discussion'], $this->Data['Comments']);

            $this->fireEvent('FetchAttachmentViews');
            if ($this->deliveryMethod() === DELIVERY_METHOD_XHTML) {
                require_once $this->fetchViewLocation('attachment', 'attachments', 'dashboard');
            }
        }

        $this->render();
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
    public function getNew($DiscussionID, $LastCommentID = 0) {
        $this->setData('Discussion', $this->DiscussionModel->getID($DiscussionID), true);

        // Check permissions.
        $this->permission('Vanilla.Discussions.View', true, 'Category', $this->Discussion->PermissionCategoryID);
        $this->setData('CategoryID', $this->CategoryID = $this->Discussion->CategoryID, true);

        // Get the comments.
        $Comments = $this->CommentModel->getNew($DiscussionID, $LastCommentID)->result();
        $this->setData('Comments', $Comments, true);

        // Set the data.
        if (count($Comments) > 0) {
            $LastComment = $Comments[count($Comments) - 1];
            // Mark the comment read.
            $this->setData('Offset', $this->Discussion->CountComments, true);
            $this->CommentModel->setWatch($this->Discussion, $this->Discussion->CountComments, $this->Discussion->CountComments, $this->Discussion->CountComments);

            $LastCommentID = $this->json('LastCommentID');
            if (is_null($LastCommentID) || $LastComment->CommentID > $LastCommentID) {
                $this->json('LastCommentID', $LastComment->CommentID);
            }
        } else {
            $this->setData('Offset', $this->CommentModel->getOffset($LastCommentID), true);
        }

        $this->View = 'comments';
        $this->render();
    }

    /**
     * Highlight route & add common JS definitions.
     *
     * Always called by dispatcher before controller's requested method.
     *
     * @since 2.0.0
     * @access public
     */
    public function initialize() {
        parent::initialize();
        $this->addDefinition('ConfirmDeleteCommentHeading', t('ConfirmDeleteCommentHeading', 'Delete Comment'));
        $this->addDefinition('ConfirmDeleteCommentText', t('ConfirmDeleteCommentText', 'Are you sure you want to delete this comment?'));
        $this->Menu->highlightRoute('/discussions');
    }

    /**
     * Display discussion page starting with a particular comment.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $CommentID Unique comment ID
     */
    public function comment($CommentID) {
        // Get the discussionID
        $Comment = $this->CommentModel->getID($CommentID);
        if (!$Comment) {
            throw notFoundException('Comment');
        }

        $DiscussionID = $Comment->DiscussionID;

        // Figure out how many comments are before this one
        $Offset = $this->CommentModel->getOffset($Comment);
        $Limit = Gdn::config('Vanilla.Comments.PerPage', 30);

        $PageNumber = pageNumber($Offset, $Limit, true);
        $this->setData('Page', $PageNumber);

        $this->View = 'index';
        $this->index($DiscussionID, 'x', $PageNumber);
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
    public function dismissAnnouncement($DiscussionID = '') {
        // Confirm announcements may be dismissed
        if (!c('Vanilla.Discussions.Dismiss', 1)) {
            throw permissionException('Vanilla.Discussions.Dismiss');
        }

        // Make sure we are posting back.
        if (!$this->Request->isPostBack()) {
            throw permissionException('Javascript');
        }

        $Session = Gdn::session();
        if (is_numeric($DiscussionID)
            && $DiscussionID > 0
            && $Session->UserID > 0
        ) {
            $this->DiscussionModel->dismissAnnouncement($DiscussionID, $Session->UserID);
        }

        // Redirect back where the user came from if necessary
        if ($this->_DeliveryType === DELIVERY_TYPE_ALL) {
            SafeRedirect('discussions');
        }

        $this->jsonTarget("#Discussion_$DiscussionID", null, 'SlideUp');

        $this->render('Blank', 'Utility', 'Dashboard');
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
    public function bookmark($DiscussionID = null) {
        // Make sure we are posting back.
        if (!$this->Request->isAuthenticatedPostBack()) {
            throw permissionException('Javascript');
        }

        $Session = Gdn::session();

        if (!$Session->UserID) {
            throw permissionException('SignedIn');
        }

        // Check the form to see if the data was posted.
        $Form = new Gdn_Form();
        $DiscussionID = $Form->getFormValue('DiscussionID', $DiscussionID);
        $Bookmark = $Form->getFormValue('Bookmark', null);
        $UserID = $Form->getFormValue('UserID', $Session->UserID);

        // Check the permission on the user.
        if ($UserID != $Session->UserID) {
            $this->permission('Garden.Moderation.Manage');
        }

        $Discussion = $this->DiscussionModel->getID($DiscussionID);
        if (!$Discussion) {
            throw notFoundException('Discussion');
        }

        $Bookmark = $this->DiscussionModel->bookmark($DiscussionID, $UserID, $Bookmark);

        // Set the new value for api calls and json targets.
        $this->setData(array(
            'UserID' => $UserID,
            'DiscussionID' => $DiscussionID,
            'Bookmarked' => (bool)$Bookmark
        ));
        setValue('Bookmarked', $Discussion, (int)$Bookmark);

        // Update the user's bookmark count
        $CountBookmarks = $this->DiscussionModel->setUserBookmarkCount($UserID);
        $this->jsonTarget('.User-CountBookmarks', (string)$CountBookmarks);

        //  Short circuit if this is an api call.
        if ($this->deliveryType() === DELIVERY_TYPE_DATA) {
            $this->render('Blank', 'Utility', 'Dashboard');
            return;
        }

        // Return the appropriate bookmark.
        require_once $this->fetchViewLocation('helper_functions', 'Discussions');
        $Html = bookmarkButton($Discussion);
//      $this->jsonTarget(".Section-DiscussionList #Discussion_$DiscussionID .Bookmark,.Section-Discussion .PageTitle .Bookmark", $Html, 'ReplaceWith');
        $this->jsonTarget("!element", $Html, 'ReplaceWith');

        // Add the bookmark to the bookmarks module.
        if ($Bookmark) {
            // Grab the individual bookmark and send it to the client.
            $Bookmarks = new BookmarkedModule($this);
            if ($CountBookmarks == 1) {
                // When there is only one bookmark we have to get the whole module.
                $Target = '#Panel';
                $Type = 'Append';
                $Bookmarks->getData();
                $Data = $Bookmarks->toString();
            } else {
                $Target = '#Bookmark_List';
                $Type = 'Prepend';
                $Loc = $Bookmarks->fetchViewLocation('discussion');

                ob_start();
                include($Loc);
                $Data = ob_get_clean();
            }

            $this->jsonTarget($Target, $Data, $Type);
        } else {
            // Send command to remove bookmark html.
            if ($CountBookmarks == 0) {
                $this->jsonTarget('#Bookmarks', null, 'Remove');
            } else {
                $this->jsonTarget('#Bookmark_'.$DiscussionID, null, 'Remove');
            }
        }

        $this->render('Blank', 'Utility', 'Dashboard');
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
    public function announce($DiscussionID = '', $Target = '') {
        $Discussion = $this->DiscussionModel->getID($DiscussionID);
        if (!$Discussion) {
            throw notFoundException('Discussion');
        }
        $this->permission('Vanilla.Discussions.Announce', true, 'Category', $Discussion->PermissionCategoryID);

        if ($this->Form->authenticatedPostBack()) {
            // Save the property.
            $CacheKeys = array(
                $this->DiscussionModel->getAnnouncementCacheKey(),
                $this->DiscussionModel->getAnnouncementCacheKey(val('CategoryID', $Discussion))
            );
            $this->DiscussionModel->SQL->cache($CacheKeys);
            $this->DiscussionModel->SetProperty($DiscussionID, 'Announce', (int)$this->Form->getFormValue('Announce', 0));

            if ($Target) {
                $this->RedirectUrl = url($Target);
            }
        } else {
            if (!$Discussion->Announce) {
                $Discussion->Announce = 2;
            }
            $this->Form->setData($Discussion);
        }

        $Discussion = (array)$Discussion;
        $Category = CategoryModel::categories($Discussion['CategoryID']);

        $this->setData('Discussion', $Discussion);
        $this->setData('Category', $Category);

        $this->title(t('Announce'));
        $this->render();
    }

    /**
     *
     *
     * @param $Discussion
     * @throws Exception
     */
    public function sendOptions($Discussion) {
        require_once $this->fetchViewLocation('helper_functions', 'Discussion');
        ob_start();
        writeDiscussionOptions($Discussion);
        $Options = ob_get_clean();

        $this->jsonTarget("#Discussion_{$Discussion->DiscussionID} .OptionsMenu,.Section-Discussion .Discussion .OptionsMenu", $Options, 'ReplaceWith');
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
    public function sink($DiscussionID = '', $Sink = true, $From = 'list') {
        // Make sure we are posting back.
        if (!$this->Request->isAuthenticatedPostBack()) {
            throw permissionException('Javascript');
        }

        $Discussion = $this->DiscussionModel->getID($DiscussionID);

        if (!$Discussion) {
            throw notFoundException('Discussion');
        }

        $this->permission('Vanilla.Discussions.Sink', true, 'Category', $Discussion->PermissionCategoryID);

        // Sink the discussion.
        $this->DiscussionModel->setField($DiscussionID, 'Sink', $Sink);
        $Discussion->Sink = $Sink;

        // Redirect to the front page
        if ($this->_DeliveryType === DELIVERY_TYPE_ALL) {
            $Target = getIncomingValue('Target', 'discussions');
            safeRedirect($Target);
        }

        $this->sendOptions($Discussion);

        $this->jsonTarget("#Discussion_$DiscussionID", null, 'Highlight');
        $this->jsonTarget(".Discussion #Item_0", null, 'Highlight');

        $this->render('Blank', 'Utility', 'Dashboard');
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
    public function close($DiscussionID = '', $Close = true, $From = 'list') {
        // Make sure we are posting back.
        if (!$this->Request->isAuthenticatedPostBack()) {
            throw permissionException('Javascript');
        }

        $Discussion = $this->DiscussionModel->getID($DiscussionID);

        if (!$Discussion) {
            throw notFoundException('Discussion');
        }

        $this->permission('Vanilla.Discussions.Close', true, 'Category', $Discussion->PermissionCategoryID);

        // Close the discussion.
        $this->DiscussionModel->setField($DiscussionID, 'Closed', $Close);
        $Discussion->Closed = $Close;

        // Redirect to the front page
        if ($this->_DeliveryType === DELIVERY_TYPE_ALL) {
            $Target = getIncomingValue('Target', 'discussions');
            safeRedirect($Target);
        }

        $this->SendOptions($Discussion);

        if ($Close) {
            require_once $this->fetchViewLocation('helper_functions', 'Discussions');
            $this->jsonTarget(".Section-DiscussionList #Discussion_$DiscussionID .Meta-Discussion", tag($Discussion, 'Closed', 'Closed'), 'Prepend');
            $this->jsonTarget(".Section-DiscussionList #Discussion_$DiscussionID", 'Closed', 'AddClass');
        } else {
            $this->jsonTarget(".Section-DiscussionList #Discussion_$DiscussionID .Tag-Closed", null, 'Remove');
            $this->jsonTarget(".Section-DiscussionList #Discussion_$DiscussionID", 'Closed', 'RemoveClass');
        }

        $this->jsonTarget("#Discussion_$DiscussionID", null, 'Highlight');
        $this->jsonTarget(".Discussion #Item_0", null, 'Highlight');

        $this->render('Blank', 'Utility', 'Dashboard');
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
    public function delete($DiscussionID, $Target = '') {
        $Discussion = $this->DiscussionModel->getID($DiscussionID);

        if (!$Discussion) {
            throw notFoundException('Discussion');
        }

        $this->permission('Vanilla.Discussions.Delete', true, 'Category', $Discussion->PermissionCategoryID);

        if ($this->Form->authenticatedPostBack()) {
            if (!$this->DiscussionModel->delete($DiscussionID)) {
                $this->Form->addError('Failed to delete discussion');
            }

            if ($this->Form->errorCount() == 0) {
                if ($this->_DeliveryType === DELIVERY_TYPE_ALL) {
                    safeRedirect($Target);
                }

                if ($Target) {
                    $this->RedirectUrl = url($Target);
                }

                $this->jsonTarget(".Section-DiscussionList #Discussion_$DiscussionID", null, 'SlideUp');
            }
        }

        $this->setData('Title', t('Delete Discussion'));
        $this->render();
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
    public function deleteComment($CommentID = '', $TransientKey = '') {
        $Session = Gdn::session();
        $DefaultTarget = '/discussions/';
        $ValidCommentID = is_numeric($CommentID) && $CommentID > 0;
        $ValidUser = $Session->UserID > 0 && $Session->validateTransientKey($TransientKey);

        if ($ValidCommentID && $ValidUser) {
            // Get comment and discussion data
            $Comment = $this->CommentModel->getID($CommentID);
            $DiscussionID = val('DiscussionID', $Comment);
            $Discussion = $this->DiscussionModel->getID($DiscussionID);

            if ($Comment && $Discussion) {
                $DefaultTarget = discussionUrl($Discussion);

                // Make sure comment is this user's or they have Delete permission
                if ($Comment->InsertUserID != $Session->UserID || !c('Vanilla.Comments.AllowSelfDelete')) {
                    $this->permission('Vanilla.Comments.Delete', true, 'Category', $Discussion->PermissionCategoryID);
                }

                // Make sure that content can (still) be edited
                $EditContentTimeout = c('Garden.EditContentTimeout', -1);
                $CanEdit = $EditContentTimeout == -1 || strtotime($Comment->DateInserted) + $EditContentTimeout > time();
                if (!$CanEdit) {
                    $this->permission('Vanilla.Comments.Delete', true, 'Category', $Discussion->PermissionCategoryID);
                }

                // Delete the comment
                if (!$this->CommentModel->delete($CommentID)) {
                    $this->Form->addError('Failed to delete comment');
                }
            } else {
                $this->Form->addError('Invalid comment');
            }
        } else {
            $this->Form->addError('ErrPermission');
        }

        // Redirect
        if ($this->_DeliveryType == DELIVERY_TYPE_ALL) {
            $Target = GetIncomingValue('Target', $DefaultTarget);
            SafeRedirect($Target);
        }

        if ($this->Form->errorCount() > 0) {
            $this->setJson('ErrorMessage', $this->Form->errors());
        } else {
            $this->jsonTarget("#Comment_$CommentID", '', 'SlideUp');
        }

        $this->render();
    }

    /**
     * Alternate version of Index that uses the embed master view.
     *
     * @param int $DiscussionID Unique identifier, if discussion has been created.
     * @param string $DiscussionStub Deprecated.
     * @param int $Offset
     * @param int $Limit
     */
    public function embed($DiscussionID = '', $DiscussionStub = '', $Offset = '', $Limit = '') {
        $this->title(t('Comments'));

        // Add theme data
        $this->Theme = c('Garden.CommentsTheme', $this->Theme);
        Gdn_Theme::section('Comments');

        // Force view options
        $this->MasterView = 'empty';
        $this->CanEditComments = false; // Don't show the comment checkboxes on the embed comments page

        // Add some css to help with the transparent bg on embedded comments
        if ($this->Head) {
            $this->Head->addString('<style type="text/css">
body { background: transparent !important; }
</style>');
        }

        // Javascript files & options
        $this->addJsFile('jquery.gardenmorepager.js');
        $this->addJsFile('jquery.autosize.min.js');
        $this->addJsFile('discussion.js');
        $this->removeJsFile('autosave.js');
        $this->addDefinition('DoInform', '0'); // Suppress inform messages on embedded page.
        $this->addDefinition('SelfUrl', Gdn::request()->PathAndQuery());
        $this->addDefinition('Embedded', true);

        // Define incoming variables (prefer querystring parameters over method parameters)
        $DiscussionID = (is_numeric($DiscussionID) && $DiscussionID > 0) ? $DiscussionID : 0;
        $DiscussionID = getIncomingValue('vanilla_discussion_id', $DiscussionID);
        $Offset = getIncomingValue('Offset', $Offset);
        $Limit = getIncomingValue('Limit', $Limit);
        $vanilla_identifier = getIncomingValue('vanilla_identifier', '');

        // Only allow vanilla identifiers of 32 chars or less - md5 if larger
        if (strlen($vanilla_identifier) > 32) {
            $vanilla_identifier = md5($vanilla_identifier);
        }
        $vanilla_type = getIncomingValue('vanilla_type', 'page');
        $vanilla_url = getIncomingValue('vanilla_url', '');
        $vanilla_category_id = getIncomingValue('vanilla_category_id', '');
        $ForeignSource = array(
            'vanilla_identifier' => $vanilla_identifier,
            'vanilla_type' => $vanilla_type,
            'vanilla_url' => $vanilla_url,
            'vanilla_category_id' => $vanilla_category_id
        );
        $this->setData('ForeignSource', $ForeignSource);

        // Set comment sorting
        $SortComments = c('Garden.Embed.SortComments') == 'desc' ? 'desc' : 'asc';
        $this->setData('SortComments', $SortComments);

        // Retrieve the discussion record
        $Discussion = false;
        if ($DiscussionID > 0) {
            $Discussion = $this->DiscussionModel->getID($DiscussionID);
        } elseif ($vanilla_identifier != '' && $vanilla_type != '') {
            $Discussion = $this->DiscussionModel->GetForeignID($vanilla_identifier, $vanilla_type);
        }

        // Set discussion data if we have one for this page
        if ($Discussion) {
            // Allow Vanilla.Comments.View to be defined to limit access to embedded comments only.
            // Otherwise, go with normal discussion view permissions. Either will do.
            $this->permission(array('Vanilla.Discussions.View', 'Vanilla.Comments.View'), false, 'Category', $Discussion->PermissionCategoryID);

            $this->setData('Discussion', $Discussion, true);
            $this->setData('DiscussionID', $Discussion->DiscussionID, true);
            $this->title($Discussion->Name);

            // Actual number of comments, excluding the discussion itself
            $ActualResponses = $Discussion->CountComments;

            // Define the query offset & limit
            if (!is_numeric($Limit) || $Limit < 0) {
                $Limit = c('Garden.Embed.CommentsPerPage', 30);
            }

            $OffsetProvided = $Offset != '';
            list($Offset, $Limit) = offsetLimit($Offset, $Limit);
            $this->Offset = $Offset;
            if (c('Vanilla.Comments.AutoOffset')) {
                if ($ActualResponses <= $Limit) {
                    $this->Offset = 0;
                }

                if ($this->Offset == $ActualResponses) {
                    $this->Offset -= $Limit;
                }
            } elseif ($this->Offset == '') {
                $this->Offset = 0;
            }

            if ($this->Offset < 0) {
                $this->Offset = 0;
            }

            // Set the canonical url to have the proper page title.
            $this->canonicalUrl(discussionUrl($Discussion, pageNumber($this->Offset, $Limit)));

            // Load the comments.
            $CurrentOrderBy = $this->CommentModel->orderBy();
            if (stringBeginsWith(GetValueR('0.0', $CurrentOrderBy), 'c.DateInserted')) {
                $this->CommentModel->orderBy('c.DateInserted '.$SortComments); // allow custom sort
            }
            $this->setData('Comments', $this->CommentModel->get($Discussion->DiscussionID, $Limit, $this->Offset), true);

            if (count($this->CommentModel->where()) > 0) {
                $ActualResponses = false;
            }

            $this->setData('_Count', $ActualResponses);

            // Build a pager
            $PagerFactory = new Gdn_PagerFactory();
            $this->EventArguments['PagerType'] = 'MorePager';
            $this->fireEvent('BeforeBuildPager');
            $this->Pager = $PagerFactory->getPager($this->EventArguments['PagerType'], $this);
            $this->Pager->ClientID = 'Pager';
            $this->Pager->MoreCode = 'More Comments';
            $this->Pager->configure(
                $this->Offset,
                $Limit,
                $ActualResponses,
                'discussion/embed/'.$Discussion->DiscussionID.'/'.Gdn_Format::url($Discussion->Name).'/%1$s'
            );
            $this->Pager->CurrentRecords = $this->Comments->numRows();
            $this->fireEvent('AfterBuildPager');
        }

        // Define the form for the comment input
        $this->Form = Gdn::Factory('Form', 'Comment');
        $this->Form->Action = url('/post/comment/');
        $this->Form->addHidden('CommentID', '');
        $this->Form->addHidden('Embedded', 'true'); // Tell the post controller that this is an embedded page (in case there are custom views it needs to pick up from a theme).
        $this->Form->addHidden('DisplayNewCommentOnly', 'true'); // Only load/display the new comment after posting (don't load all new comments since the page last loaded).

        // Grab the page title
        if ($this->Request->get('title')) {
            $this->Form->setValue('Name', $this->Request->get('title'));
        }

        // Set existing DiscussionID for comment form
        if ($Discussion) {
            $this->Form->addHidden('DiscussionID', $Discussion->DiscussionID);
        }

        foreach ($ForeignSource as $Key => $Val) {
            // Drop the foreign source information into the form so it can be used if creating a discussion
            $this->Form->addHidden($Key, $Val);

            // Also drop it into the definitions so it can be picked up for stashing comments
            $this->addDefinition($Key, $Val);
        }

        // Retrieve & apply the draft if there is one:
        $Draft = false;
        if (Gdn::session()->UserID && $Discussion) {
            $DraftModel = new DraftModel();
            $Draft = $DraftModel->get(Gdn::session()->UserID, 0, 1, $Discussion->DiscussionID)->firstRow();
            $this->Form->addHidden('DraftID', $Draft ? $Draft->DraftID : '');
        }

        if ($Draft) {
            $this->Form->setFormValue('Body', $Draft->Body);
        } else {
            // Look in the session stash for a comment
            $StashComment = Gdn::session()->getPublicStash('CommentForForeignID_'.$ForeignSource['vanilla_identifier']);
            if ($StashComment) {
                $this->Form->setValue('Body', $StashComment);
                $this->Form->setFormValue('Body', $StashComment);
            }
        }

        // Deliver JSON data if necessary
        if ($this->_DeliveryType != DELIVERY_TYPE_ALL) {
            if ($this->Discussion) {
                $this->setJson('LessRow', $this->Pager->toString('less'));
                $this->setJson('MoreRow', $this->Pager->toString('more'));
            }
            $this->View = 'comments';
        }

        // Ordering note for JS
        if ($SortComments == 'desc') {
            $this->addDefinition('PrependNewComments', '1');
        }

        // Report the discussion id so js can use it.
        if ($Discussion) {
            $this->addDefinition('DiscussionID', $Discussion->DiscussionID);
        }

        $this->fireEvent('BeforeDiscussionRender');
        $this->render();
    }

    /**
     * Redirect to the url specified by the discussion.
     * @param array|object $Discussion
     */
    protected function redirectDiscussion($Discussion) {
        $Body = Gdn_Format::to(val('Body', $Discussion), val('Format', $Discussion));
        if (preg_match('`href="([^"]+)"`i', $Body, $Matches)) {
            $Url = $Matches[1];
            safeRedirect($Url, 301);
        }
    }

    /**
     * Re-fetch a discussion's content based on its foreign url.
     * @param type $DiscussionID
     */
    public function refetchPageInfo($DiscussionID) {
        // Make sure we are posting back.
        if (!$this->Request->isAuthenticatedPostBack(true)) {
            throw permissionException('Javascript');
        }

        // Grab the discussion.
        $Discussion = $this->DiscussionModel->getID($DiscussionID);

        if (!$Discussion) {
            throw notFoundException('Discussion');
        }

        // Make sure the user has permission to edit this discussion.
        $this->permission('Vanilla.Discussions.Edit', true, 'Category', $Discussion->PermissionCategoryID);

        $ForeignUrl = valr('Attributes.ForeignUrl', $Discussion);
        if (!$ForeignUrl) {
            throw new Gdn_UserException(t("This discussion isn't associated with a url."));
        }

        $Stub = $this->DiscussionModel->fetchPageInfo($ForeignUrl, true);

        // Save the stub.
        $this->DiscussionModel->setField($DiscussionID, (array)$Stub);

        // Send some of the stuff back.
        if (isset($Stub['Name'])) {
            $this->jsonTarget('.PageTitle h1', Gdn_Format::text($Stub['Name']));
        }
        if (isset($Stub['Body'])) {
            $this->jsonTarget("#Discussion_$DiscussionID .Message", Gdn_Format::to($Stub['Body'], $Stub['Format']));
        }

        $this->informMessage('The page was successfully fetched.');

        $this->render('Blank', 'Utility', 'Dashboard');
    }

    protected function _setOpenGraph() {
        if (!$this->Head) {
            return;
        }
        $this->Head->addTag('meta', array('property' => 'og:type', 'content' => 'article'));
    }
}
