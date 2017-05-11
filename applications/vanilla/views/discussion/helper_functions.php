<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

if (!defined('APPLICATION')) {
    exit();
}


if (!function_exists('formatBody')) :
    /**
     * Format content of comment or discussion.
     *
     * Event argument for $Object will be 'Comment' or 'Discussion'.
     *
     * @since 2.1
     * @param DataSet $Object Comment or discussion.
     * @return string Parsed body.
     */
    function formatBody($Object) {
        Gdn::controller()->fireEvent('BeforeCommentBody');
        $Object->FormatBody = Gdn_Format::to($Object->Body, $Object->Format);
        Gdn::controller()->fireEvent('AfterCommentFormat');

        return $Object->FormatBody;
    }
endif;

if (!function_exists('writeBookmarkLink')) :
    /**
     * Output link to (un)boomark a discussion.
     */
    function writeBookmarkLink() {
        if (!Gdn::session()->isValid()) {
            return '';
        }

        $Discussion = Gdn::controller()->data('Discussion');

        // Bookmark link
        $Title = t($Discussion->Bookmarked == '1' ? 'Unbookmark' : 'Bookmark');
        echo anchor(
            $Title,
            '/discussion/bookmark/'.$Discussion->DiscussionID.'/'.Gdn::session()->transientKey().'?Target='.urlencode(Gdn::controller()->SelfUrl),
            'Hijack Bookmark'.($Discussion->Bookmarked == '1' ? ' Bookmarked' : ''),
            ['title' => $Title]
        );
    }
endif;

if (!function_exists('writeComment')) :
    /**
     * Outputs a formatted comment.
     *
     * Prior to 2.1, this also output the discussion ("FirstComment") to the browser.
     * That has moved to the discussion.php view.
     *
     * @param DataSet $Comment .
     * @param Gdn_Controller $Sender .
     * @param Gdn_Session $Session .
     * @param int $CurrentOffet How many comments into the discussion we are (for anchors).
     */
    function writeComment($Comment, $Sender, $Session, $CurrentOffset) {
        // Whether to order the name & photo with the latter first.
        static $UserPhotoFirst = null;

        if ($UserPhotoFirst === null) {
            $UserPhotoFirst = c('Vanilla.Comment.UserPhotoFirst', true);
        }
        $Author = Gdn::userModel()->getID($Comment->InsertUserID); //UserBuilder($Comment, 'Insert');
        $Permalink = val('Url', $Comment, '/discussion/comment/'.$Comment->CommentID.'/#Comment_'.$Comment->CommentID);

        // Set CanEditComments (whether to show checkboxes)
        if (!property_exists($Sender, 'CanEditComments')) {
            $Sender->CanEditComments = $Session->checkPermission('Vanilla.Comments.Edit', true, 'Category', 'any') && c('Vanilla.AdminCheckboxes.Use');
        }
        // Prep event args
        $CssClass = cssClass($Comment, $CurrentOffset);
        $Sender->EventArguments['Comment'] = &$Comment;
        $Sender->EventArguments['Author'] = &$Author;
        $Sender->EventArguments['CssClass'] = &$CssClass;
        $Sender->EventArguments['CurrentOffset'] = $CurrentOffset;
        $Sender->EventArguments['Permalink'] = $Permalink;

        // Needed in writeCommentOptions()
        if ($Sender->data('Discussion', null) === null) {
            $discussionModel = new DiscussionModel();
            $discussion = $discussionModel->getID($Comment->DiscussionID);
            $Sender->setData('Discussion', $discussion);
        }

        // DEPRECATED ARGUMENTS (as of 2.1)
        $Sender->EventArguments['Object'] = &$Comment;
        $Sender->EventArguments['Type'] = 'Comment';

        // First comment template event
        $Sender->fireEvent('BeforeCommentDisplay'); ?>
        <li class="<?php echo $CssClass; ?>" id="<?php echo 'Comment_'.$Comment->CommentID; ?>">
            <div class="Comment">

                <?php
                // Write a stub for the latest comment so it's easy to link to it from outside.
                if ($CurrentOffset == Gdn::controller()->data('_LatestItem')) {
                    echo '<span id="latest"></span>';
                }
                ?>
                <div class="Options">
                    <?php writeCommentOptions($Comment); ?>
                </div>
                <?php $Sender->fireEvent('BeforeCommentMeta'); ?>
                <div class="Item-Header CommentHeader">
                    <div class="AuthorWrap">
            <span class="Author">
               <?php
               if ($UserPhotoFirst) {
                   echo userPhoto($Author);
                   echo userAnchor($Author, 'Username');
               } else {
                   echo userAnchor($Author, 'Username');
                   echo userPhoto($Author);
               }
               echo FormatMeAction($Comment);
               $Sender->fireEvent('AuthorPhoto');
               ?>
            </span>
            <span class="AuthorInfo">
               <?php
               echo ' '.wrapIf(htmlspecialchars(val('Title', $Author)), 'span', array('class' => 'MItem AuthorTitle'));
               echo ' '.wrapIf(htmlspecialchars(val('Location', $Author)), 'span', array('class' => 'MItem AuthorLocation'));
               $Sender->fireEvent('AuthorInfo');
               ?>
            </span>
                    </div>
                    <div class="Meta CommentMeta CommentInfo">
            <span class="MItem DateCreated">
               <?php echo anchor(Gdn_Format::date($Comment->DateInserted, 'html'), $Permalink, 'Permalink', array('name' => 'Item_'.($CurrentOffset), 'rel' => 'nofollow')); ?>
            </span>
                        <?php
                        echo DateUpdated($Comment, array('<span class="MItem">', '</span>'));
                        ?>
                        <?php
                        // Include source if one was set
                        if ($Source = val('Source', $Comment)) {
                            echo wrap(sprintf(t('via %s'), t($Source.' Source', $Source)), 'span', array('class' => 'MItem Source'));
                        }

                        // Include IP Address if we have permission
                        if ($Session->checkPermission('Garden.PersonalInfo.View')) {
                            echo wrap(ipAnchor($Comment->InsertIPAddress), 'span', array('class' => 'MItem IPAddress'));
                        }

                        $Sender->fireEvent('CommentInfo');
                        $Sender->fireEvent('InsideCommentMeta'); // DEPRECATED
                        $Sender->fireEvent('AfterCommentMeta'); // DEPRECATED
                        ?>
                    </div>
                </div>
                <div class="Item-BodyWrap">
                    <div class="Item-Body">
                        <div class="Message">
                            <?php
                            echo formatBody($Comment);
                            ?>
                        </div>
                        <?php
                        $Sender->fireEvent('AfterCommentBody');
                        writeReactions($Comment);
                        if (val('Attachments', $Comment)) {
                            writeAttachments($Comment->Attachments);
                        }
                        ?>
                    </div>
                </div>
            </div>
        </li>
        <?php
        $Sender->fireEvent('AfterComment');
    }
endif;

if (!function_exists('discussionOptionsToDropdown')):
    /**
     * @param array $options
     * @param DropdownModule|null $dropdown
     * @return DropdownModule
     */
    function discussionOptionsToDropdown($options, $dropdown = null) {
        if (is_null($dropdown)) {
            $dropdown = new DropdownModule('dropdown', '', 'OptionsMenu');
        }

        if (!empty($options)) {
            foreach ($options as $option) {
                $dropdown->addLink(val('Label', $option), val('Url', $option), strtolower(val('Label', $option)), val('Class', $option));
            }
        }

        return $dropdown;
    }
endif;

if (!function_exists('getDiscussionOptions')) :
    /**
     * Get options for the current discussion.
     *
     * @since 2.1
     * @param DataSet $Discussion .
     * @return array $Options Each element must include keys 'Label' and 'Url'.
     */
    function getDiscussionOptions($Discussion = null) {
        $Options = array();

        $Sender = Gdn::controller();
        $Session = Gdn::session();

        if ($Discussion == null) {
            $Discussion = $Sender->data('Discussion');
        }
        $CategoryID = val('CategoryID', $Discussion);
        if (!$CategoryID && property_exists($Sender, 'Discussion')) {
            $CategoryID = val('CategoryID', $Sender->Discussion);
        }

        // Build the $Options array based on current user's permission.
        // Can the user edit the discussion?
        $CanEdit = DiscussionModel::canEdit($Discussion, $TimeLeft);
        if ($CanEdit) {
            if ($TimeLeft) {
                $TimeLeft = ' ('.Gdn_Format::seconds($TimeLeft).')';
            }
            $Options['EditDiscussion'] = array('Label' => t('Edit').$TimeLeft, 'Url' => '/post/editdiscussion/'.$Discussion->DiscussionID);
        }

        // Can the user announce?
        if (CategoryModel::checkPermission($CategoryID, 'Vanilla.Discussions.Announce')) {
            $Options['AnnounceDiscussion'] = [
                'Label' => t('Announce'),
                'Url' => '/discussion/announce?discussionid='.$Discussion->DiscussionID.'&Target='.urlencode($Sender->SelfUrl.'#Head'),
                'Class' => 'AnnounceDiscussion Popup'
            ];
        }

        // Can the user sink?
        if (CategoryModel::checkPermission($CategoryID, 'Vanilla.Discussions.Sink')) {
            $NewSink = (int)!$Discussion->Sink;
            $Options['SinkDiscussion'] = [
                'Label' => t($Discussion->Sink ? 'Unsink' : 'Sink'),
                'Url' => "/discussion/sink?discussionid={$Discussion->DiscussionID}&sink=$NewSink",
                'Class' => 'SinkDiscussion Hijack'
            ];
        }

        // Can the user close?
        if (CategoryModel::checkPermission($CategoryID, 'Vanilla.Discussions.Close')) {
            $NewClosed = (int)!$Discussion->Closed;
            $Options['CloseDiscussion'] = [
                'Label' => t($Discussion->Closed ? 'Reopen' : 'Close'),
                'Url' => "/discussion/close?discussionid={$Discussion->DiscussionID}&close=$NewClosed",
                'Class' => 'CloseDiscussion Hijack'
            ];
        }

        if ($CanEdit && valr('Attributes.ForeignUrl', $Discussion)) {
            $Options['RefetchPage'] = [
                'Label' => t('Refetch Page'),
                'Url' => '/discussion/refetchpageinfo.json?discussionid='.$Discussion->DiscussionID,
                'Class' => 'RefetchPage Hijack'
            ];
        }

        // Can the user move?
        if ($CanEdit && $Session->checkPermission('Garden.Moderation.Manage')) {
            $Options['MoveDiscussion'] = [
                'Label' => t('Move'),
                'Url' => '/moderation/confirmdiscussionmoves?discussionid='.$Discussion->DiscussionID,
                'Class' => 'MoveDiscussion Popup'
            ];
        }

        // Can the user delete?
        if (CategoryModel::checkPermission($CategoryID, 'Vanilla.Discussions.Delete')) {
            $Category = CategoryModel::categories($CategoryID);
            $Options['DeleteDiscussion'] = [
                'Label' => t('Delete Discussion'),
                'Url' => '/discussion/delete?discussionid='.$Discussion->DiscussionID.'&target='.urlencode(categoryUrl($Category)),
                'Class' => 'DeleteDiscussion Popup'
            ];
        }

        // DEPRECATED (as of 2.1)
        $Sender->EventArguments['Type'] = 'Discussion';

        // Allow plugins to add options.
        $Sender->EventArguments['DiscussionOptions'] = &$Options;
        $Sender->EventArguments['Discussion'] = $Discussion;
        $Sender->fireEvent('DiscussionOptions');

        return $Options;
    }
endif;


if (!function_exists('getDiscussionOptionsDropdown')):
    /**
     * Constructs an options dropdown menu for a discussion.
     *
     * @param object|array|null $discussion The discussion to get the dropdown options for.
     * @return DropdownModule A dropdown consisting of discussion options.
     * @throws Exception
     */
    function getDiscussionOptionsDropdown($discussion = null) {
        $dropdown = new DropdownModule('dropdown', '', 'OptionsMenu');
        $sender = Gdn::controller();
        $session = Gdn::session();

        if ($discussion == null) {
            $discussion = $sender->data('Discussion');
        }

        $categoryID = val('CategoryID', $discussion);

        if (!$categoryID && property_exists($sender, 'Discussion')) {
            trace('Getting category ID from controller Discussion property.');
            $categoryID = val('CategoryID', $sender->Discussion);
        }

        $discussionID = $discussion->DiscussionID;
        $categoryUrl = urlencode(categoryUrl(CategoryModel::categories($categoryID)));

        // Permissions
        $canEdit = DiscussionModel::canEdit($discussion, $timeLeft);
        $canAnnounce = CategoryModel::checkPermission($categoryID, 'Vanilla.Discussions.Announce');
        $canSink = CategoryModel::checkPermission($categoryID, 'Vanilla.Discussions.Sink');
        $canClose = CategoryModel::checkPermission($categoryID, 'Vanilla.Discussions.Close');
        $canDelete = CategoryModel::checkPermission($categoryID, 'Vanilla.Discussions.Delete');
        $canMove = $canEdit && $session->checkPermission('Garden.Moderation.Manage');
        $canRefetch = $canEdit && valr('Attributes.ForeignUrl', $discussion);
        $canDismiss = c('Vanilla.Discussions.Dismiss', 1) && $discussion->Announce == '1' && $discussion->Dismissed != '1' && $session->isValid();
        $canTag = c('Tagging.Discussions.Enabled') && checkPermission('Vanilla.Tagging.Add') && in_array(strtolower($sender->ControllerName), ['discussionscontroller', 'categoriescontroller']) ;

        if ($canEdit && $timeLeft) {
            $timeLeft = ' ('.Gdn_Format::seconds($timeLeft).')';
        }

        $dropdown->addLinkIf($canDismiss, t('Dismiss'), "vanilla/discussion/dismissannouncement?discussionid={$discussionID}", 'dismiss', 'DismissAnnouncement Hijack')
            ->addLinkIf($canEdit, t('Edit').$timeLeft, '/post/editdiscussion/'.$discussionID, 'edit')
            ->addLinkIf($canAnnounce, t('Announce'), '/discussion/announce?discussionid='.$discussionID, 'announce', 'AnnounceDiscussion Popup')
            ->addLinkIf($canSink, t($discussion->Sink ? 'Unsink' : 'Sink'), '/discussion/sink?discussionid='.$discussionID.'&sink='.(int)!$discussion->Sink, 'sink', 'SinkDiscussion Hijack')
            ->addLinkIf($canClose, t($discussion->Closed ? 'Reopen' : 'Close'), '/discussion/close?discussionid='.$discussionID.'&close='.(int)!$discussion->Closed, 'close', 'CloseDiscussion Hijack')
            ->addLinkIf($canRefetch, t('Refetch Page'), '/discussion/refetchpageinfo.json?discussionid='.$discussionID, 'refetch', 'RefetchPage Hijack')
            ->addLinkIf($canMove, t('Move'), '/moderation/confirmdiscussionmoves?discussionid='.$discussionID, 'move', 'MoveDiscussion Popup')
            ->addLinkIf($canTag, t('Tag'), '/discussion/tag?discussionid='.$discussionID, 'tag', 'TagDiscussion Popup')
            ->addLinkIf($canDelete, t('Delete Discussion'), '/discussion/delete?discussionid='.$discussionID.'&target='.$categoryUrl, 'delete', 'DeleteDiscussion Popup');

        // DEPRECATED
        $options = [];
        $sender->EventArguments['DiscussionOptions'] = &$options;
        $sender->EventArguments['Discussion'] = $discussion;
        $sender->fireEvent('DiscussionOptions');

        // Backwards compatability
        $dropdown = discussionOptionsToDropdown($options, $dropdown);

        // Allow plugins to edit the dropdown.
        $sender->EventArguments['DiscussionOptionsDropdown'] = &$dropdown;
        $sender->EventArguments['Discussion'] = $discussion;
        $sender->fireEvent('DiscussionOptionsDropdown');

        return $dropdown;
    }
endif;

/**
 * Output moderation checkbox.
 *
 * @since 2.1
 */
if (!function_exists('WriteAdminCheck')):
    function writeAdminCheck($Object = null) {
        if (!Gdn::controller()->CanEditComments || !c('Vanilla.AdminCheckboxes.Use')) {
            return;
        }
        echo '<span class="AdminCheck"><input type="checkbox" name="Toggle"></span>';
    }
endif;

/**
 * Output discussion options.
 *
 * @since 2.1
 */
if (!function_exists('writeDiscussionOptions')):
    function writeDiscussionOptions($Discussion = null) {
        deprecated('writeDiscussionOptions', 'getDiscussionOptionsDropdown', 'March 2016');

        $Options = getDiscussionOptions($Discussion);

        if (empty($Options)) {
            return;
        }

        echo ' <span class="ToggleFlyout OptionsMenu">';
        echo '<span class="OptionsTitle" title="'.t('Options').'">'.t('Options').'</span>';
        echo sprite('SpFlyoutHandle', 'Arrow');
        echo '<ul class="Flyout MenuItems" style="display: none;">';
        foreach ($Options as $Code => $Option) {
            echo wrap(anchor($Option['Label'], $Option['Url'], val('Class', $Option, $Code)), 'li');
        }
        echo '</ul>';
        echo '</span>';
    }
endif;

if (!function_exists('getCommentOptions')) :
    /**
     * Get comment options.
     *
     * @since 2.1
     * @param DataSet $Comment .
     * @return array $Options Each element must include keys 'Label' and 'Url'.
     */
    function getCommentOptions($Comment) {
        $Options = array();

        if (!is_numeric(val('CommentID', $Comment))) {
            return $Options;
        }

        $Sender = Gdn::controller();
        $Session = Gdn::session();
        $Discussion = Gdn::controller()->data('Discussion');

        $categoryID = val('CategoryID', $Discussion);

        // Determine if we still have time to edit
        $EditContentTimeout = c('Garden.EditContentTimeout', -1);
        $CanEdit = $EditContentTimeout == -1 || strtotime($Comment->DateInserted) + $EditContentTimeout > time();
        $TimeLeft = '';
        $canEditDiscussions = CategoryModel::checkPermission($categoryID, 'Vanilla.Discussions.Edit');
        if ($CanEdit && $EditContentTimeout > 0 && !$canEditDiscussions) {
            $TimeLeft = strtotime($Comment->DateInserted) + $EditContentTimeout - time();
            $TimeLeft = $TimeLeft > 0 ? ' ('.Gdn_Format::seconds($TimeLeft).')' : '';
        }

        // Can the user edit the comment?
        $canEditComments = CategoryModel::checkPermission($categoryID, 'Vanilla.Comments.Edit');
        if (($CanEdit && $Session->UserID == $Comment->InsertUserID) || $canEditComments) {
            $Options['EditComment'] = [
                'Label' => t('Edit').$TimeLeft,
                'Url' => '/post/editcomment/'.$Comment->CommentID,
                'EditComment'
            ];
        }

        // Can the user delete the comment?
        $SelfDeleting = ($CanEdit && $Session->UserID == $Comment->InsertUserID && c('Vanilla.Comments.AllowSelfDelete'));
        if ($SelfDeleting || CategoryModel::checkPermission($categoryID, 'Vanilla.Comments.Delete')) {
            $Options['DeleteComment'] = [
                'Label' => t('Delete'),
                'Url' => '/discussion/deletecomment/'.$Comment->CommentID.'/'.$Session->transientKey().'/?Target='.urlencode("/discussion/{$Comment->DiscussionID}/x"),
                'Class' => 'DeleteComment'
            ];
        }

        // DEPRECATED (as of 2.1)
        $Sender->EventArguments['Type'] = 'Comment';

        // Allow plugins to add options
        $Sender->EventArguments['CommentOptions'] = &$Options;
        $Sender->EventArguments['Comment'] = $Comment;
        $Sender->fireEvent('CommentOptions');

        return $Options;
    }
endif;

if (!function_exists('writeCommentOptions')) :
    /**
     * Output comment options.
     *
     * @since 2.1
     * @param DataSet $Comment
     */
    function writeCommentOptions($Comment) {
        $Controller = Gdn::controller();
        $Session = Gdn::session();

        $Id = $Comment->CommentID;
        $Options = getCommentOptions($Comment);
        if (empty($Options)) {
            return;
        }

        echo '<span class="ToggleFlyout OptionsMenu">';
        echo '<span class="OptionsTitle" title="'.t('Options').'">'.t('Options').'</span>';
        echo sprite('SpFlyoutHandle', 'Arrow');
        echo '<ul class="Flyout MenuItems">';
        foreach ($Options as $Code => $Option) {
            echo wrap(anchor($Option['Label'], $Option['Url'], val('Class', $Option, $Code)), 'li');
        }
        echo '</ul>';
        echo '</span>';
        if (c('Vanilla.AdminCheckboxes.Use')) {
            // Only show the checkbox if the user has permission to affect multiple items
            $Discussion = Gdn::controller()->data('Discussion');
            if (CategoryModel::checkPermission(val('CategoryID', $Discussion), 'Vanilla.Comments.Delete')) {
                if (!property_exists($Controller, 'CheckedComments')) {
                    $Controller->CheckedComments = $Session->getAttribute('CheckedComments', array());
                }
                $ItemSelected = inSubArray($Id, $Controller->CheckedComments);
                echo '<span class="AdminCheck"><input type="checkbox" name="'.'Comment'.'ID[]" value="'.$Id.'"'.($ItemSelected ? ' checked="checked"' : '').' /></span>';
            }
        }
    }
endif;

if (!function_exists('writeCommentForm')) :
    /**
     * Output comment form.
     *
     * @since 2.1
     */
    function writeCommentForm() {
        $Session = Gdn::session();
        $Controller = Gdn::controller();

        $Discussion = $Controller->data('Discussion');
        $categoryID = val('CategoryID', $Discussion);
        $UserCanClose = CategoryModel::checkPermission($categoryID, 'Vanilla.Discussions.Close');
        $UserCanComment = CategoryModel::checkPermission($categoryID, 'Vanilla.Comments.Add');

        // Closed notification
        if ($Discussion->Closed == '1') {
            ?>
            <div class="Foot Closed">
                <div class="Note Closed"><?php echo t('This discussion has been closed.'); ?></div>
            </div>
        <?php
        } elseif (!$UserCanComment) {
            if (!Gdn::session()->isValid()) {
                ?>
                <div class="Foot Closed">
                    <div class="Note Closed SignInOrRegister"><?php
                        $Popup = (c('Garden.SignIn.Popup')) ? ' class="Popup"' : '';
                        $ReturnUrl = Gdn::request()->pathAndQuery();
                        echo formatString(
                            t('Sign In or Register to Comment.', '<a href="{SignInUrl,html}"{Popup}>Sign In</a> or <a href="{RegisterUrl,html}">Register</a> to comment.'),
                            array(
                                'SignInUrl' => url(signInUrl($ReturnUrl)),
                                'RegisterUrl' => url(registerUrl($ReturnUrl)),
                                'Popup' => $Popup
                            )
                        ); ?>
                    </div>
                    <?php //echo anchor(t('All Discussions'), 'discussions', 'TabLink'); ?>
                </div>
            <?php
            }
        }

        if (($Discussion->Closed == '1' && $UserCanClose) || ($Discussion->Closed == '0' && $UserCanComment)) {
            echo $Controller->fetchView('comment', 'post', 'vanilla');
        }
    }
endif;

if (!function_exists('writeCommentFormHeader')) :
    /**
     *
     */
    function writeCommentFormHeader() {
        $Session = Gdn::session();
        if (c('Vanilla.Comment.UserPhotoFirst', true)) {
            echo userPhoto($Session->User);
            echo userAnchor($Session->User, 'Username');
        } else {
            echo userAnchor($Session->User, 'Username');
            echo userPhoto($Session->User);
        }
    }
endif;

if (!function_exists('writeEmbedCommentForm')) :
    /**
     *
     */
    function writeEmbedCommentForm() {
        $Session = Gdn::session();
        $Controller = Gdn::controller();
        $Discussion = $Controller->data('Discussion');

        if ($Discussion && $Discussion->Closed == '1') {
            ?>
            <div class="Foot Closed">
                <div class="Note Closed"><?php echo t('This discussion has been closed.'); ?></div>
            </div>
        <?php } else { ?>
            <h2><?php echo t('Leave a comment'); ?></h2>
            <div class="MessageForm CommentForm EmbedCommentForm">
                <?php
                echo $Controller->Form->open(array('id' => 'Form_Comment'));
                echo $Controller->Form->errors();
                echo $Controller->Form->hidden('Name');
                echo wrap($Controller->Form->textBox('Body', array('MultiLine' => true)), 'div', array('class' => 'TextBoxWrapper'));
                echo "<div class=\"Buttons\">\n";

                $AllowSigninPopup = c('Garden.SignIn.Popup');
                $Attributes = ['target' => '_top'];

                // If we aren't ajaxing this call then we need to target the url of the parent frame.
                $ReturnUrl = $Controller->data('ForeignSource.vanilla_url', Gdn::request()->pathAndQuery());

                if ($Session->isValid()) {
                    $AuthenticationUrl = Gdn::authenticator()->signOutUrl($ReturnUrl);
                    echo wrap(
                        sprintf(
                            t('Commenting as %1$s (%2$s)', 'Commenting as %1$s <span class="SignOutWrap">(%2$s)</span>'),
                            Gdn_Format::text($Session->User->Name),
                            anchor(t('Sign Out'), $AuthenticationUrl, 'SignOut', $Attributes)
                        ),
                        'div',
                        ['class' => 'Author']
                    );
                    echo $Controller->Form->button('Post Comment', array('class' => 'Button CommentButton'));
                } else {
                    $AuthenticationUrl = url(signInUrl($ReturnUrl), true);
                    if ($AllowSigninPopup) {
                        $CssClass = 'SignInPopup Button Stash';
                    } else {
                        $CssClass = 'Button Stash';
                    }

                    echo anchor(t('Comment As ...'), $AuthenticationUrl, $CssClass, $Attributes);
                }
                echo "</div>\n";
                echo $Controller->Form->close();
                ?>
            </div>
        <?php
        }
    }
endif;

if (!function_exists('isMeAction')) :
    /**
     *
     *
     * @param $Row
     * @return bool|void
     */
    function isMeAction($Row) {
        if (!c('Garden.Format.MeActions')) {
            return;
        }
        $Row = (array)$Row;
        if (!array_key_exists('Body', $Row)) {
            return false;
        }

        return strpos(trim($Row['Body']), '/me ') === 0;
    }
endif;

if (!function_exists('formatMeAction')) :
    /**
     *
     *
     * @param $Comment
     * @return string|void
     */
    function formatMeAction($Comment) {
        if (!isMeAction($Comment) || !c('Garden.Format.MeActions')) {
            return;
        }

        // Maxlength (don't let people blow up the forum)
        $Comment->Body = substr($Comment->Body, 4);
        $Maxlength = c('Vanilla.MeAction.MaxLength', 100);
        $Body = formatBody($Comment);
        if (strlen($Body) > $Maxlength) {
            $Body = substr($Body, 0, $Maxlength).'...';
        }

        return '<div class="AuthorAction">'.$Body.'</div>';
    }
endif;
