<?php
/**
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

if (!defined('APPLICATION')) {
    exit();
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
if (!function_exists('FormatBody')):
    function formatBody($Object) {
        Gdn::controller()->fireEvent('BeforeCommentBody');
        $Object->FormatBody = Gdn_Format::to($Object->Body, $Object->Format);
        Gdn::controller()->fireEvent('AfterCommentFormat');

        return $Object->FormatBody;
    }
endif;

/**
 * Output link to (un)boomark a discussion.
 */
if (!function_exists('WriteBookmarkLink')):
    function writeBookmarkLink() {
        if (!Gdn::session()->isValid())
            return '';

        $Discussion = Gdn::controller()->data('Discussion');

        // Bookmark link
        $Title = t($Discussion->Bookmarked == '1' ? 'Unbookmark' : 'Bookmark');
        echo anchor(
            $Title,
            '/discussion/bookmark/'.$Discussion->DiscussionID.'/'.Gdn::session()->TransientKey().'?Target='.urlencode(Gdn::controller()->SelfUrl),
            'Hijack Bookmark'.($Discussion->Bookmarked == '1' ? ' Bookmarked' : ''),
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
 * @param DataSet $Comment .
 * @param Gdn_Controller $Sender .
 * @param Gdn_Session $Session .
 * @param int $CurrentOffet How many comments into the discussion we are (for anchors).
 */
if (!function_exists('WriteComment')):
    function writeComment($Comment, $Sender, $Session, $CurrentOffset) {
        static $UserPhotoFirst = NULL;
        if ($UserPhotoFirst === null)
            $UserPhotoFirst = c('Vanilla.Comment.UserPhotoFirst', true);
        $Author = Gdn::userModel()->getID($Comment->InsertUserID); //UserBuilder($Comment, 'Insert');
        $Permalink = val('Url', $Comment, '/discussion/comment/'.$Comment->CommentID.'/#Comment_'.$Comment->CommentID);

        // Set CanEditComments (whether to show checkboxes)
        if (!property_exists($Sender, 'CanEditComments'))
            $Sender->CanEditComments = $Session->checkPermission('Vanilla.Comments.Edit', TRUE, 'Category', 'any') && c('Vanilla.AdminCheckboxes.Use');

        // Prep event args
        $CssClass = CssClass($Comment, $CurrentOffset);
        $Sender->EventArguments['Comment'] = &$Comment;
        $Sender->EventArguments['Author'] = &$Author;
        $Sender->EventArguments['CssClass'] = &$CssClass;
        $Sender->EventArguments['CurrentOffset'] = $CurrentOffset;
        $Sender->EventArguments['Permalink'] = $Permalink;

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
                    <?php WriteCommentOptions($Comment); ?>
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
               echo ' '.WrapIf(htmlspecialchars(val('Title', $Author)), 'span', array('class' => 'MItem AuthorTitle'));
               echo ' '.WrapIf(htmlspecialchars(val('Location', $Author)), 'span', array('class' => 'MItem AuthorLocation'));
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
                        if ($Source = val('Source', $Comment))
                            echo wrap(sprintf(t('via %s'), t($Source.' Source', $Source)), 'span', array('class' => 'MItem Source'));

                        $Sender->fireEvent('CommentInfo');
                        $Sender->fireEvent('InsideCommentMeta'); // DEPRECATED
                        $Sender->fireEvent('AfterCommentMeta'); // DEPRECATED

                        // Include IP Address if we have permission
                        if ($Session->checkPermission('Garden.PersonalInfo.View'))
                            echo wrap(IPAnchor($Comment->InsertIPAddress), 'span', array('class' => 'MItem IPAddress'));

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
                        $Sender->fireEvent('AfterCommentBody');
                        WriteReactions($Comment);
                        if (val('Attachments', $Comment)) {
                            WriteAttachments($Comment->Attachments);
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

/**
 * Get options for the current discussion.
 *
 * @since 2.1
 * @param DataSet $Discussion .
 * @return array $Options Each element must include keys 'Label' and 'Url'.
 */
if (!function_exists('GetDiscussionOptions')):
    function getDiscussionOptions($Discussion = null) {
        $Options = array();

        $Sender = Gdn::controller();
        $Session = Gdn::session();

        if ($Discussion == null)
            $Discussion = $Sender->data('Discussion');

        $CategoryID = val('CategoryID', $Discussion);
        if (!$CategoryID && property_exists($Sender, 'Discussion'))
            $CategoryID = val('CategoryID', $Sender->Discussion);
        $PermissionCategoryID = val('PermissionCategoryID', $Discussion, val('PermissionCategoryID', $Discussion));

        // Build the $Options array based on current user's permission.
        // Can the user edit the discussion?
        $CanEdit = DiscussionModel::canEdit($Discussion, $TimeLeft);
        if ($CanEdit) {
            if ($TimeLeft) {
                $TimeLeft = ' ('.Gdn_Format::Seconds($TimeLeft).')';
            }
            $Options['EditDiscussion'] = array('Label' => t('Edit').$TimeLeft, 'Url' => '/post/editdiscussion/'.$Discussion->DiscussionID);
        }

        // Can the user announce?
        if ($Session->checkPermission('Vanilla.Discussions.Announce', TRUE, 'Category', $PermissionCategoryID))
            $Options['AnnounceDiscussion'] = array('Label' => t('Announce'), 'Url' => '/discussion/announce?discussionid='.$Discussion->DiscussionID.'&Target='.urlencode($Sender->SelfUrl.'#Head'), 'Class' => 'AnnounceDiscussion Popup');

        // Can the user sink?
        if ($Session->checkPermission('Vanilla.Discussions.Sink', TRUE, 'Category', $PermissionCategoryID)) {
            $NewSink = (int)!$Discussion->Sink;
            $Options['SinkDiscussion'] = array('Label' => t($Discussion->Sink ? 'Unsink' : 'Sink'), 'Url' => "/discussion/sink?discussionid={$Discussion->DiscussionID}&sink=$NewSink", 'Class' => 'SinkDiscussion Hijack');
        }

        // Can the user close?
        if ($Session->checkPermission('Vanilla.Discussions.Close', TRUE, 'Category', $PermissionCategoryID)) {
            $NewClosed = (int)!$Discussion->Closed;
            $Options['CloseDiscussion'] = array('Label' => t($Discussion->Closed ? 'Reopen' : 'Close'), 'Url' => "/discussion/close?discussionid={$Discussion->DiscussionID}&close=$NewClosed", 'Class' => 'CloseDiscussion Hijack');
        }

        if ($CanEdit && valr('Attributes.ForeignUrl', $Discussion)) {
            $Options['RefetchPage'] = array('Label' => t('Refetch Page'), 'Url' => '/discussion/refetchpageinfo.json?discussionid='.$Discussion->DiscussionID, 'Class' => 'RefetchPage Hijack');
        }

        // Can the user move?
        if ($CanEdit && $Session->checkPermission('Garden.Moderation.Manage')) {
            $Options['MoveDiscussion'] = array('Label' => t('Move'), 'Url' => '/moderation/confirmdiscussionmoves?discussionid='.$Discussion->DiscussionID, 'Class' => 'MoveDiscussion Popup');
        }

        // Can the user delete?
        if ($Session->checkPermission('Vanilla.Discussions.Delete', TRUE, 'Category', $PermissionCategoryID)) {
            $Category = CategoryModel::categories($CategoryID);

            $Options['DeleteDiscussion'] = array('Label' => t('Delete Discussion'), 'Url' => '/discussion/delete?discussionid='.$Discussion->DiscussionID.'&target='.urlencode(CategoryUrl($Category)), 'Class' => 'DeleteDiscussion Popup');
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

/**
 * Output moderation checkbox.
 *
 * @since 2.1
 */
if (!function_exists('WriteAdminCheck')):
    function writeAdminCheck($Object = null) {
        if (!Gdn::controller()->CanEditComments || !c('Vanilla.AdminCheckboxes.Use'))
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
    function writeDiscussionOptions($Discussion = null) {
        $Options = GetDiscussionOptions($Discussion);

        if (empty($Options))
            return;

        echo ' <span class="ToggleFlyout OptionsMenu">';
        echo '<span class="OptionsTitle" title="'.t('Options').'">'.t('Options').'</span>';
        echo sprite('SpFlyoutHandle', 'Arrow');
        echo '<ul class="Flyout MenuItems" style="display: none;">';
        foreach ($Options as $Code => $Option):
            echo wrap(Anchor($Option['Label'], $Option['Url'], val('Class', $Option, $Code)), 'li');
        endforeach;
        echo '</ul>';
        echo '</span>';
    }
endif;

/**
 * Get comment options.
 *
 * @since 2.1
 * @param DataSet $Comment .
 * @return array $Options Each element must include keys 'Label' and 'Url'.
 */
if (!function_exists('GetCommentOptions')):
    function getCommentOptions($Comment) {
        $Options = array();

        if (!is_numeric(val('CommentID', $Comment)))
            return $Options;

        $Sender = Gdn::controller();
        $Session = Gdn::session();
        $Discussion = Gdn::controller()->data('Discussion');

        $CategoryID = val('CategoryID', $Discussion);
        $PermissionCategoryID = val('PermissionCategoryID', $Discussion);

        // Determine if we still have time to edit
        $EditContentTimeout = c('Garden.EditContentTimeout', -1);
        $CanEdit = $EditContentTimeout == -1 || strtotime($Comment->DateInserted) + $EditContentTimeout > time();
        $TimeLeft = '';
        if ($CanEdit && $EditContentTimeout > 0 && !$Session->checkPermission('Vanilla.Discussions.Edit', TRUE, 'Category', $PermissionCategoryID)) {
            $TimeLeft = strtotime($Comment->DateInserted) + $EditContentTimeout - time();
            $TimeLeft = $TimeLeft > 0 ? ' ('.Gdn_Format::Seconds($TimeLeft).')' : '';
        }

        // Can the user edit the comment?
        if (($CanEdit && $Session->UserID == $Comment->InsertUserID) || $Session->checkPermission('Vanilla.Comments.Edit', TRUE, 'Category', $PermissionCategoryID))
            $Options['EditComment'] = array('Label' => t('Edit').$TimeLeft, 'Url' => '/post/editcomment/'.$Comment->CommentID, 'EditComment');

        // Can the user delete the comment?
        $SelfDeleting = ($CanEdit && $Session->UserID == $Comment->InsertUserID && c('Vanilla.Comments.AllowSelfDelete'));
        if ($SelfDeleting || $Session->checkPermission('Vanilla.Comments.Delete', TRUE, 'Category', $PermissionCategoryID))
            $Options['DeleteComment'] = array('Label' => t('Delete'), 'Url' => '/discussion/deletecomment/'.$Comment->CommentID.'/'.$Session->TransientKey().'/?Target='.urlencode("/discussion/{$Comment->DiscussionID}/x"), 'Class' => 'DeleteComment');

        // DEPRECATED (as of 2.1)
        $Sender->EventArguments['Type'] = 'Comment';

        // Allow plugins to add options
        $Sender->EventArguments['CommentOptions'] = &$Options;
        $Sender->EventArguments['Comment'] = $Comment;
        $Sender->fireEvent('CommentOptions');

        return $Options;
    }
endif;
/**
 * Output comment options.
 *
 * @since 2.1
 * @param DataSet $Comment .
 */
if (!function_exists('WriteCommentOptions')):
    function writeCommentOptions($Comment) {
        $Controller = Gdn::controller();
        $Session = Gdn::session();

        $Id = $Comment->CommentID;
        $Options = GetCommentOptions($Comment);
        if (empty($Options))
            return;

        echo '<span class="ToggleFlyout OptionsMenu">';
        echo '<span class="OptionsTitle" title="'.t('Options').'">'.t('Options').'</span>';
        echo sprite('SpFlyoutHandle', 'Arrow');
        echo '<ul class="Flyout MenuItems">';
        foreach ($Options as $Code => $Option):
            echo wrap(Anchor($Option['Label'], $Option['Url'], val('Class', $Option, $Code)), 'li');
        endforeach;
        echo '</ul>';
        echo '</span>';
        if (c('Vanilla.AdminCheckboxes.Use')) {
            // Only show the checkbox if the user has permission to affect multiple items
            $Discussion = Gdn::controller()->data('Discussion');
            $PermissionCategoryID = val('PermissionCategoryID', $Discussion);
            if ($Session->checkPermission('Vanilla.Comments.Delete', TRUE, 'Category', $PermissionCategoryID)) {
                if (!property_exists($Controller, 'CheckedComments'))
                    $Controller->CheckedComments = $Session->getAttribute('CheckedComments', array());

                $ItemSelected = InSubArray($Id, $Controller->CheckedComments);
                echo '<span class="AdminCheck"><input type="checkbox" name="'.'Comment'.'ID[]" value="'.$Id.'"'.($ItemSelected ? ' checked="checked"' : '').' /></span>';
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
    function writeCommentForm() {
        $Session = Gdn::session();
        $Controller = Gdn::controller();

        $Discussion = $Controller->data('Discussion');
        $PermissionCategoryID = val('PermissionCategoryID', $Discussion);
        $UserCanClose = $Session->checkPermission('Vanilla.Discussions.Close', TRUE, 'Category', $PermissionCategoryID);
        $UserCanComment = $Session->checkPermission('Vanilla.Comments.Add', TRUE, 'Category', $PermissionCategoryID);

        // Closed notification
        if ($Discussion->Closed == '1') {
            ?>
            <div class="Foot Closed">
                <div class="Note Closed"><?php echo t('This discussion has been closed.'); ?></div>
                <?php //echo anchor(t('All Discussions'), 'discussions', 'TabLink'); ?>
            </div>
        <?php
        } else if (!$UserCanComment) {
            if (!Gdn::session()->isValid()) {
                ?>
                <div class="Foot Closed">
                    <div class="Note Closed SignInOrRegister"><?php
                        $Popup = (c('Garden.SignIn.Popup')) ? ' class="Popup"' : '';
                        $ReturnUrl = Gdn::request()->PathAndQuery();
                        echo formatString(
                            t('Sign In or Register to Comment.', '<a href="{SignInUrl,html}"{Popup}>Sign In</a> or <a href="{RegisterUrl,html}">Register</a> to comment.'),
                            array(
                                'SignInUrl' => url(SignInUrl($ReturnUrl)),
                                'RegisterUrl' => url(RegisterUrl($ReturnUrl)),
                                'Popup' => $Popup
                            )
                        ); ?>
                    </div>
                    <?php //echo anchor(t('All Discussions'), 'discussions', 'TabLink'); ?>
                </div>
            <?php
            }
        }

        if (($Discussion->Closed == '1' && $UserCanClose) || ($Discussion->Closed == '0' && $UserCanComment))
            echo $Controller->fetchView('comment', 'post', 'vanilla');
    }
endif;

if (!function_exists('WriteCommentFormHeader')):
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

if (!function_exists('WriteEmbedCommentForm')):
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
                echo $Controller->Form->Hidden('Name');
                echo wrap($Controller->Form->textBox('Body', array('MultiLine' => TRUE)), 'div', array('class' => 'TextBoxWrapper'));
                echo "<div class=\"Buttons\">\n";

                $AllowSigninPopup = c('Garden.SignIn.Popup');
                $Attributes = array('tabindex' => '-1', 'target' => '_top');

                // If we aren't ajaxing this call then we need to target the url of the parent frame.
                $ReturnUrl = $Controller->data('ForeignSource.vanilla_url', Gdn::request()->PathAndQuery());

                if ($Session->isValid()) {
                    $AuthenticationUrl = Gdn::authenticator()->SignOutUrl($ReturnUrl);
                    echo wrap(
                        sprintf(
                            t('Commenting as %1$s (%2$s)', 'Commenting as %1$s <span class="SignOutWrap">(%2$s)</span>'),
                            Gdn_Format::text($Session->User->Name),
                            anchor(t('Sign Out'), $AuthenticationUrl, 'SignOut', $Attributes)
                        ),
                        'div',
                        array('class' => 'Author')
                    );
                    echo $Controller->Form->button('Post Comment', array('class' => 'Button CommentButton'));
                } else {
                    $AuthenticationUrl = url(SignInUrl($ReturnUrl), true);
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

if (!function_exists('IsMeAction')):
    function isMeAction($Row) {
        if (!c('Garden.Format.MeActions'))
            return;
        $Row = (array)$Row;
        if (!array_key_exists('Body', $Row))
            return FALSE;

        return strpos(trim($Row['Body']), '/me ') === 0;
    }
endif;

if (!function_exists('FormatMeAction')):
    function formatMeAction($Comment) {
        if (!IsMeAction($Comment) || !C('Garden.Format.MeActions'))
            return;

        // Maxlength (don't let people blow up the forum)
        $Comment->Body = substr($Comment->Body, 4);
        $Maxlength = c('Vanilla.MeAction.MaxLength', 100);
        $Body = FormatBody($Comment);
        if (strlen($Body) > $Maxlength)
            $Body = substr($Body, 0, $Maxlength).'...';

        return '<div class="AuthorAction">'.$Body.'</div>';
    }
endif;
