<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

if (!defined('APPLICATION')) {
    exit();
}


if (!function_exists('formatBody')) :
    /**
     * Format content of comment or discussion.
     *
     * Event argument for $object will be 'Comment' or 'Discussion'.
     *
     * @since 2.1
     * @param DataSet $object Comment or discussion.
     * @return string Parsed body.
     */
    function formatBody($object) {
        Gdn::controller()->fireEvent('BeforeCommentBody');
        $object->FormatBody = Gdn_Format::to($object->Body, $object->Format);
        Gdn::controller()->fireEvent('AfterCommentFormat');

        return $object->FormatBody;
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

        $discussion = Gdn::controller()->data('Discussion');

        // Bookmark link
        $title = t($discussion->Bookmarked == '1' ? 'Unbookmark' : 'Bookmark');
        echo anchor(
            $title,
            '/discussion/bookmark/'.$discussion->DiscussionID.'/'.Gdn::session()->transientKey().'?Target='.urlencode(Gdn::controller()->SelfUrl),
            'Hijack Bookmark'.($discussion->Bookmarked == '1' ? ' Bookmarked' : ''),
            ['title' => $title]
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
     * @param DataSet $comment .
     * @param Gdn_Controller $sender .
     * @param Gdn_Session $session .
     * @param int $CurrentOffet How many comments into the discussion we are (for anchors).
     */
    function writeComment($comment, $sender, $session, $currentOffset) {
        // Whether to order the name & photo with the latter first.
        static $userPhotoFirst = null;

        if ($userPhotoFirst === null) {
            $userPhotoFirst = c('Vanilla.Comment.UserPhotoFirst', true);
        }
        $author = Gdn::userModel()->getID($comment->InsertUserID); //UserBuilder($Comment, 'Insert');
        $permalink = val('Url', $comment, '/discussion/comment/'.$comment->CommentID.'/#Comment_'.$comment->CommentID);

        // Set CanEditComments (whether to show checkboxes)
        if (!property_exists($sender, 'CanEditComments')) {
            $sender->CanEditComments = $session->checkPermission('Vanilla.Comments.Edit', true, 'Category', 'any') && c('Vanilla.AdminCheckboxes.Use');
        }
        // Prep event args
        $cssClass = cssClass($comment, $currentOffset);
        $sender->EventArguments['Comment'] = &$comment;
        $sender->EventArguments['Author'] = &$author;
        $sender->EventArguments['CssClass'] = &$cssClass;
        $sender->EventArguments['CurrentOffset'] = $currentOffset;
        $sender->EventArguments['Permalink'] = $permalink;

        // Needed in writeCommentOptions()
        if ($sender->data('Discussion', null) === null) {
            $discussionModel = new DiscussionModel();
            $discussion = $discussionModel->getID($comment->DiscussionID);
            $sender->setData('Discussion', $discussion);
        }

        // DEPRECATED ARGUMENTS (as of 2.1)
        $sender->EventArguments['Object'] = &$comment;
        $sender->EventArguments['Type'] = 'Comment';

        // First comment template event
        $sender->fireEvent('BeforeCommentDisplay'); ?>
        <li class="<?php echo $cssClass; ?>" id="<?php echo 'Comment_'.$comment->CommentID; ?>">
            <div class="Comment">

                <?php
                // Write a stub for the latest comment so it's easy to link to it from outside.
                if ($currentOffset == Gdn::controller()->data('_LatestItem')) {
                    echo '<span id="latest"></span>';
                }
                ?>
                <div class="Options">
                    <?php writeCommentOptions($comment); ?>
                </div>
                <?php $sender->fireEvent('BeforeCommentMeta'); ?>
                <div class="Item-Header CommentHeader">
                    <div class="AuthorWrap">
            <span class="Author">
               <?php
               if ($userPhotoFirst) {
                   echo userPhoto($author);
                   echo userAnchor($author, 'Username');
               } else {
                   echo userAnchor($author, 'Username');
                   echo userPhoto($author);
               }
               echo formatMeAction($comment);
               $sender->fireEvent('AuthorPhoto');
               ?>
            </span>
            <span class="AuthorInfo">
               <?php
               echo ' '.wrapIf(htmlspecialchars(val('Title', $author)), 'span', ['class' => 'MItem AuthorTitle']);
               echo ' '.wrapIf(htmlspecialchars(val('Location', $author)), 'span', ['class' => 'MItem AuthorLocation']);
               $sender->fireEvent('AuthorInfo');
               ?>
            </span>
                    </div>
                    <div class="Meta CommentMeta CommentInfo">
            <span class="MItem DateCreated">
               <?php echo anchor(Gdn_Format::date($comment->DateInserted, 'html'), $permalink, 'Permalink', ['name' => 'Item_'.($currentOffset), 'rel' => 'nofollow']); ?>
            </span>
                        <?php
                        echo dateUpdated($comment, ['<span class="MItem">', '</span>']);
                        ?>
                        <?php
                        // Include source if one was set
                        if ($source = val('Source', $comment)) {
                            echo wrap(sprintf(t('via %s'), t($source.' Source', $source)), 'span', ['class' => 'MItem Source']);
                        }

                        // Include IP Address if we have permission
                        if ($session->checkPermission('Garden.PersonalInfo.View')) {
                            echo wrap(ipAnchor($comment->InsertIPAddress), 'span', ['class' => 'MItem IPAddress']);
                        }

                        $sender->fireEvent('CommentInfo');
                        $sender->fireEvent('InsideCommentMeta'); // DEPRECATED
                        $sender->fireEvent('AfterCommentMeta'); // DEPRECATED
                        ?>
                    </div>
                </div>
                <div class="Item-BodyWrap">
                    <div class="Item-Body">
                        <div class="Message userContent">
                            <?php
                            echo formatBody($comment);
                            ?>
                        </div>
                        <?php
                        $sender->fireEvent('AfterCommentBody');
                        writeReactions($comment);
                        if (val('Attachments', $comment)) {
                            writeAttachments($comment->Attachments);
                        }
                        ?>
                    </div>
                </div>
            </div>
        </li>
        <?php
        $sender->fireEvent('AfterComment');
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
                $dropdown->addLink(val('Label', $option), val('Url', $option), NavModule::textToKey(val('Label', $option)), val('Class', $option));
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
     * @param DataSet $discussion .
     * @return array $options Each element must include keys 'Label' and 'Url'.
     */
    function getDiscussionOptions($discussion = null) {
        $options = [];

        $sender = Gdn::controller();
        $session = Gdn::session();

        if ($discussion == null) {
            $discussion = $sender->data('Discussion');
        }
        $categoryID = val('CategoryID', $discussion);
        if (!$categoryID && property_exists($sender, 'Discussion')) {
            $categoryID = val('CategoryID', $sender->Discussion);
        }

        // Build the $Options array based on current user's permission.
        // Can the user edit the discussion?
        $canEdit = DiscussionModel::canEdit($discussion, $timeLeft);
        if ($canEdit) {
            if ($timeLeft) {
                $timeLeft = ' ('.Gdn_Format::seconds($timeLeft).')';
            }
            $options['EditDiscussion'] = ['Label' => t('Edit').$timeLeft, 'Url' => '/post/editdiscussion/'.$discussion->DiscussionID];
        }

        // Can the user announce?
        if (CategoryModel::checkPermission($categoryID, 'Vanilla.Discussions.Announce')) {
            $options['AnnounceDiscussion'] = [
                'Label' => t('Announce'),
                'Url' => '/discussion/announce?discussionid='.$discussion->DiscussionID.'&Target='.urlencode($sender->SelfUrl.'#Head'),
                'Class' => 'AnnounceDiscussion Popup'
            ];
        }

        // Can the user sink?
        if (CategoryModel::checkPermission($categoryID, 'Vanilla.Discussions.Sink')) {
            $newSink = (int)!$discussion->Sink;
            $options['SinkDiscussion'] = [
                'Label' => t($discussion->Sink ? 'Unsink' : 'Sink'),
                'Url' => "/discussion/sink?discussionid={$discussion->DiscussionID}&sink=$newSink",
                'Class' => 'SinkDiscussion Hijack'
            ];
        }

        // Can the user close?
        if (CategoryModel::checkPermission($categoryID, 'Vanilla.Discussions.Close')) {
            $newClosed = (int)!$discussion->Closed;
            $options['CloseDiscussion'] = [
                'Label' => t($discussion->Closed ? 'Reopen' : 'Close'),
                'Url' => "/discussion/close?discussionid={$discussion->DiscussionID}&close=$newClosed",
                'Class' => 'CloseDiscussion Hijack'
            ];
        }

        if ($canEdit && valr('Attributes.ForeignUrl', $discussion)) {
            $options['RefetchPage'] = [
                'Label' => t('Refetch Page'),
                'Url' => '/discussion/refetchpageinfo.json?discussionid='.$discussion->DiscussionID,
                'Class' => 'RefetchPage Hijack'
            ];
        }

        // Can the user move?
        if ($canEdit && $session->checkPermission('Garden.Moderation.Manage')) {
            $options['MoveDiscussion'] = [
                'Label' => t('Move'),
                'Url' => '/moderation/confirmdiscussionmoves?discussionid='.$discussion->DiscussionID,
                'Class' => 'MoveDiscussion Popup'
            ];
        }

        // Can the user delete?
        if (CategoryModel::checkPermission($categoryID, 'Vanilla.Discussions.Delete')) {
            $category = CategoryModel::categories($categoryID);
            $options['DeleteDiscussion'] = [
                'Label' => t('Delete Discussion'),
                'Url' => '/discussion/delete?discussionid='.$discussion->DiscussionID.'&target='.urlencode(categoryUrl($category)),
                'Class' => 'DeleteDiscussion Popup'
            ];
        }

        // DEPRECATED (as of 2.1)
        $sender->EventArguments['Type'] = 'Discussion';

        // Allow plugins to add options.
        $sender->EventArguments['DiscussionOptions'] = &$options;
        $sender->EventArguments['Discussion'] = $discussion;
        $sender->fireEvent('DiscussionOptions');

        return $options;
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
        $canDismiss = c('Vanilla.Discussions.Dismiss', 1)
            && $discussion->Announce
            && !$discussion->Dismissed
            && $session->isValid();
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
    function writeAdminCheck($object = null) {
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
    function writeDiscussionOptions($discussion = null) {
        deprecated('writeDiscussionOptions', 'getDiscussionOptionsDropdown', 'March 2016');

        $options = getDiscussionOptions($discussion);

        if (empty($options)) {
            return;
        }

        echo ' <span class="ToggleFlyout OptionsMenu">';
        echo '<span class="OptionsTitle" title="'.t('Options').'">'.t('Options').'</span>';
        echo sprite('SpFlyoutHandle', 'Arrow');
        echo '<ul class="Flyout MenuItems" style="display: none;">';
        foreach ($options as $code => $option) {
            echo wrap(anchor($option['Label'], $option['Url'], val('Class', $option, $code)), 'li');
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
     * @param DataSet $comment .
     * @return array $options Each element must include keys 'Label' and 'Url'.
     */
    function getCommentOptions($comment) {
        $options = [];

        if (!is_numeric(val('CommentID', $comment))) {
            return $options;
        }

        $sender = Gdn::controller();
        $session = Gdn::session();
        $discussion = Gdn::controller()->data('Discussion');

        $categoryID = val('CategoryID', $discussion);

        // Can the user edit the comment?
        $canEdit = CommentModel::canEdit($comment, $timeLeft, $discussion);
        if ($canEdit) {
            if ($timeLeft) {
                $timeLeft = ' ('.Gdn_Format::seconds($timeLeft).')';
            }
            $options['EditComment'] = [
                'Label' => t('Edit').$timeLeft,
                'Url' => '/post/editcomment/'.$comment->CommentID,
                'EditComment'
            ];
        }

        // Can the user delete the comment?
        $canDelete = CategoryModel::checkPermission(
            $categoryID,
            'Vanilla.Comments.Delete'
        );
        $canSelfDelete = ($canEdit && $session->UserID == $comment->InsertUserID && c('Vanilla.Comments.AllowSelfDelete'));
        if ($canDelete || $canSelfDelete) {
            $options['DeleteComment'] = [
                'Label' => t('Delete'),
                'Url' => '/discussion/deletecomment/'.$comment->CommentID.'/'.$session->transientKey().'/?Target='.urlencode("/discussion/{$comment->DiscussionID}/x"),
                'Class' => 'DeleteComment'
            ];
        }

        // DEPRECATED (as of 2.1)
        $sender->EventArguments['Type'] = 'Comment';

        // Allow plugins to add options
        $sender->EventArguments['CommentOptions'] = &$options;
        $sender->EventArguments['Comment'] = $comment;
        $sender->fireEvent('CommentOptions');

        return $options;
    }
endif;

if (!function_exists('writeCommentOptions')) :
    /**
     * Output comment options.
     *
     * @since 2.1
     * @param DataSet $comment
     */
    function writeCommentOptions($comment) {
        $controller = Gdn::controller();
        $session = Gdn::session();

        $id = $comment->CommentID;
        $options = getCommentOptions($comment);
        if (empty($options)) {
            return;
        }

        echo '<span class="ToggleFlyout OptionsMenu">';
        echo '<span class="OptionsTitle" title="'.t('Options').'">'.t('Options').'</span>';
        echo sprite('SpFlyoutHandle', 'Arrow');
        echo '<ul class="Flyout MenuItems">';
        foreach ($options as $code => $option) {
            echo wrap(anchor($option['Label'], $option['Url'], val('Class', $option, $code)), 'li');
        }
        echo '</ul>';
        echo '</span>';
        if (c('Vanilla.AdminCheckboxes.Use')) {
            // Only show the checkbox if the user has permission to affect multiple items
            $discussion = Gdn::controller()->data('Discussion');
            if (CategoryModel::checkPermission(val('CategoryID', $discussion), 'Vanilla.Comments.Delete')) {
                if (!property_exists($controller, 'CheckedComments')) {
                    $controller->CheckedComments = $session->getAttribute('CheckedComments', []);
                }
                $itemSelected = inSubArray($id, $controller->CheckedComments);
                echo '<span class="AdminCheck"><input type="checkbox" name="'.'Comment'.'ID[]" value="'.$id.'"'.($itemSelected ? ' checked="checked"' : '').' /></span>';
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
        $session = Gdn::session();
        $controller = Gdn::controller();

        $discussion = $controller->data('Discussion');
        $categoryID = val('CategoryID', $discussion);
        $userCanClose = CategoryModel::checkPermission($categoryID, 'Vanilla.Discussions.Close');
        $userCanComment = CategoryModel::checkPermission($categoryID, 'Vanilla.Comments.Add');

        // Closed notification
        if ($discussion->Closed == '1') {
            ?>
            <div class="Foot Closed">
                <div class="Note Closed"><?php echo t('This discussion has been closed.'); ?></div>
            </div>
        <?php
        } elseif (!$userCanComment) {
            if (!Gdn::session()->isValid()) {
                ?>
                <div class="Foot Closed">
                    <div class="Note Closed SignInOrRegister"><?php
                        $popup = (c('Garden.SignIn.Popup')) ? ' class="Popup"' : '';
                        $returnUrl = Gdn::request()->pathAndQuery();
                        echo formatString(
                            t('Sign In or Register to Comment.', '<a href="{SignInUrl,html}"{Popup}>Sign In</a> or <a href="{RegisterUrl,html}">Register</a> to comment.'),
                            [
                                'SignInUrl' => url(signInUrl($returnUrl)),
                                'RegisterUrl' => url(registerUrl($returnUrl)),
                                'Popup' => $popup
                            ]
                        ); ?>
                    </div>
                    <?php //echo anchor(t('All Discussions'), 'discussions', 'TabLink'); ?>
                </div>
            <?php
            }
        }

        if (($discussion->Closed == '1' && $userCanClose) || ($discussion->Closed == '0' && $userCanComment)) {
            echo $controller->fetchView('comment', 'post', 'vanilla');
        }
    }
endif;

if (!function_exists('writeCommentFormHeader')) :
    /**
     *
     */
    function writeCommentFormHeader() {
        $session = Gdn::session();
        if (c('Vanilla.Comment.UserPhotoFirst', true)) {
            echo userPhoto($session->User);
            echo userAnchor($session->User, 'Username');
        } else {
            echo userAnchor($session->User, 'Username');
            echo userPhoto($session->User);
        }
    }
endif;

if (!function_exists('writeEmbedCommentForm')) :
    /**
     *
     */
    function writeEmbedCommentForm() {
        $session = Gdn::session();
        $controller = Gdn::controller();
        $discussion = $controller->data('Discussion');

        if ($discussion && $discussion->Closed == '1') {
            ?>
            <div class="Foot Closed">
                <div class="Note Closed"><?php echo t('This discussion has been closed.'); ?></div>
            </div>
        <?php } else { ?>
            <h2><?php echo t('Leave a comment'); ?></h2>
            <div class="MessageForm CommentForm EmbedCommentForm">
                <?php
                echo $controller->Form->open(['id' => 'Form_Comment']);
                echo $controller->Form->errors();
                echo $controller->Form->hidden('Name');
                echo wrap($controller->Form->textBox('Body', ['MultiLine' => true]), 'div', ['class' => 'TextBoxWrapper']);
                echo "<div class=\"Buttons\">\n";

                $allowSigninPopup = c('Garden.SignIn.Popup');
                $attributes = ['target' => '_top'];

                // If we aren't ajaxing this call then we need to target the url of the parent frame.
                $returnUrl = $controller->data('ForeignSource.vanilla_url', Gdn::request()->pathAndQuery());

                if ($session->isValid()) {
                    $authenticationUrl = Gdn::authenticator()->signOutUrl($returnUrl);
                    echo wrap(
                        sprintf(
                            t('Commenting as %1$s (%2$s)', 'Commenting as %1$s <span class="SignOutWrap">(%2$s)</span>'),
                            Gdn_Format::text($session->User->Name),
                            anchor(t('Sign Out'), $authenticationUrl, 'SignOut', $attributes)
                        ),
                        'div',
                        ['class' => 'Author']
                    );
                    echo $controller->Form->button('Post Comment', ['class' => 'Button CommentButton']);
                } else {
                    $authenticationUrl = url(signInUrl($returnUrl), true);
                    if ($allowSigninPopup) {
                        $cssClass = 'SignInPopup Button Stash';
                    } else {
                        $cssClass = 'Button Stash';
                    }

                    echo anchor(t('Comment As ...'), $authenticationUrl, $cssClass, $attributes);
                }
                echo "</div>\n";
                echo $controller->Form->close();
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
     * @param $row
     * @return bool|void
     */
    function isMeAction($row) {
        if (!c('Garden.Format.MeActions')) {
            return;
        }
        $row = (array)$row;
        if (!array_key_exists('Body', $row)) {
            return false;
        }

        return strpos(trim($row['Body']), '/me ') === 0;
    }
endif;

if (!function_exists('formatMeAction')) :
    /**
     *
     *
     * @param $comment
     * @return string|void
     */
    function formatMeAction($comment) {
        if (!isMeAction($comment) || !c('Garden.Format.MeActions')) {
            return;
        }

        // Maxlength (don't let people blow up the forum)
        $comment->Body = substr($comment->Body, 4);
        $maxlength = c('Vanilla.MeAction.MaxLength', 100);
        $body = formatBody($comment);
        if (strlen($body) > $maxlength) {
            $body = substr($body, 0, $maxlength).'...';
        }

        return '<div class="AuthorAction">'.$body.'</div>';
    }
endif;
