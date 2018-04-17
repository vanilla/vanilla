<?php if (!defined('APPLICATION')) exit();

if (!function_exists('WriteDiscussionHeading')) :
    /**
     * Write the table heading.
     */
    function writeDiscussionHeading() {
        ?>
        <tr>
            <?php echo adminCheck(NULL, ['<td class="CheckBoxColumn"><div class="Wrap">', '</div></td>']); ?>
            <td class="DiscussionName" role="columnheader">
                <div class="Wrap"><?php echo discussionHeading() ?></div>
            </td>
            <td class="BlockColumn BlockColumn-User FirstUser" role="columnheader">
                <div class="Wrap"><?php echo t('Started By'); ?></div>
            </td>
            <td class="BigCount CountReplies" role="columnheader">
                <div class="Wrap"><?php echo t('Replies'); ?></div>
            </td>
            <td class="BigCount CountViews" role="columnheader">
                <div class="Wrap"><?php echo t('Views'); ?></div>
            </td>
            <td class="BlockColumn BlockColumn-User LastUser" role="columnheader">
                <div class="Wrap"><?php echo t('Most Recent Comment', 'Most Recent'); ?></div>
            </td>
        </tr>
    <?php
    }
endif;

if (!function_exists('writeDiscussionRow')) :
    /**
     * Writes a discussion in table row format.
     */
    function writeDiscussionRow($discussion, $sender, $session) {
        if (!property_exists($sender, 'CanEditDiscussions')) {
            $sender->CanEditDiscussions = val('PermsDiscussionsEdit', CategoryModel::categories($discussion->CategoryID)) && c('Vanilla.AdminCheckboxes.Use');
        }
        $cssClass = cssClass($discussion);
        $discussionUrl = $discussion->Url;

        if ($session->UserID) {
            $discussionUrl .= '#latest';
        }
        $sender->EventArguments['DiscussionUrl'] = &$discussionUrl;
        $sender->EventArguments['Discussion'] = &$discussion;
        $sender->EventArguments['CssClass'] = &$cssClass;

        $first = userBuilder($discussion, 'First');
        if ($discussion->LastUserID) {
            $last = userBuilder($discussion, 'Last');
        } else {
            $last = $first;
        }
        $sender->EventArguments['FirstUser'] = &$first;
        $sender->EventArguments['LastUser'] = &$last;

        $sender->fireEvent('BeforeDiscussionName');

        $discussionName = $discussion->Name;
        // If there are no word character detected in the title treat it as if it is blank.
        if (!preg_match('/\w/u', $discussionName)) {
            $discussionName = t('Blank Discussion Topic');
        }
        $sender->EventArguments['DiscussionName'] = &$discussionName;

        static $firstDiscussion = true;
        if (!$firstDiscussion) {
            $sender->fireEvent('BetweenDiscussion');
        } else {
            $firstDiscussion = false;
        }

        $discussion->CountPages = ceil($discussion->CountComments / $sender->CountCommentsPerPage);

        $firstPageUrl = discussionUrl($discussion, 1);

        if (isset($discussion->LastCommentID)) {
            $lastComment = [
                'CommentID' => $discussion->LastCommentID,
                'DiscussionID' => $discussion->DiscussionID,
                'CategoryID' => $discussion->CategoryID,
            ];

            $lastPageUrl = commentUrl($lastComment);
        } else {
            $lastPageUrl = $firstPageUrl;
        }
        ?>
        <tr id="Discussion_<?php echo $discussion->DiscussionID; ?>" class="<?php echo $cssClass; ?>">
            <?php $sender->fireEvent('BeforeDiscussionContent'); ?>
            <?php echo adminCheck($discussion, ['<td class="CheckBoxColumn"><div class="Wrap">', '</div></td>']); ?>
            <td class="DiscussionName">
                <div class="Wrap">
         <span class="Options">
            <?php
            echo optionsList($discussion);
            echo bookmarkButton($discussion);
            ?>
         </span>
                    <?php
                    $sender->fireEvent('BeforeDiscussionTitle');
                    echo anchor($discussionName, $discussionUrl, 'Title').' ';
                    $sender->fireEvent('AfterDiscussionTitle');

                    writeMiniPager($discussion);
                    echo newComments($discussion);
                    if ($sender->data('_ShowCategoryLink', true)) {
                        echo categoryLink($discussion, ' '.t('in').' ');
                    }
                    // Other stuff that was in the standard view that you may want to display:
                    echo '<div class="Meta Meta-Discussion">';
                    writeTags($discussion);
                    echo '</div>';

                    //			if ($Source = val('Source', $Discussion))
                    //				echo ' '.sprintf(t('via %s'), t($Source.' Source', $Source));
                    //
                    ?>
                </div>
            </td>
            <td class="BlockColumn BlockColumn-User FirstUser">
                <div class="Block Wrap">
                    <?php
                    echo userPhoto($first, ['Size' => 'Small']);
                    echo userAnchor($first, 'UserLink BlockTitle');
                    echo '<div class="Meta">';
                    echo anchor(Gdn_Format::date($discussion->FirstDate, 'html'), $firstPageUrl, 'CommentDate MItem');
                    echo '</div>';
                    ?>
                </div>
            </td>
            <td class="BigCount CountComments">
                <div class="Wrap">
                    <?php
                    // Exact Number
                    // echo number_format($Discussion->CountComments);

                    // Round Number
                    echo bigPlural($discussion->CountComments, '%s comment');
                    ?>
                </div>
            </td>
            <td class="BigCount CountViews">
                <div class="Wrap">
                    <?php
                    // Exact Number
                    // echo number_format($Discussion->CountViews);

                    // Round Number
                    echo bigPlural($discussion->CountViews, '%s view');
                    ?>
                </div>
            </td>
            <td class="BlockColumn BlockColumn-User LastUser">
                <div class="Block Wrap">
                    <?php
                    if ($last) {
                        echo userPhoto($last, ['Size' => 'Small']);
                        echo userAnchor($last, 'UserLink BlockTitle');
                        echo '<div class="Meta">';
                        echo anchor(Gdn_Format::date($discussion->LastDate, 'html'), $lastPageUrl, 'CommentDate MItem');
                        echo '</div>';
                    } else {
                        echo '&nbsp;';
                    }
                    ?>
                </div>
            </td>
        </tr>
    <?php
    }

endif;

if (!function_exists('WriteDiscussionTable')) :
    /**
     * Write the discussion table wrapper.
     */
    function writeDiscussionTable() {
        $c = Gdn::controller();
        ?>
        <div class="DataTableWrap">
            <h2 class="sr-only"><?php echo t('Discussion List'); ?></h2>
            <table class="DataTable DiscussionsTable">
                <thead>
                <?php
                writeDiscussionHeading();
                ?>
                </thead>
                <tbody>
                <?php
                $session = Gdn::session();
                $announcements = $c->data('Announcements');
                if (is_a($announcements, 'Gdn_DataSet')) {
                    foreach ($announcements->result() as $discussion) {
                        writeDiscussionRow($discussion, $c, $session);
                    }
                }

                $discussions = $c->data('Discussions');
                if (is_a($discussions, 'Gdn_DataSet')) {
                    foreach ($discussions->result() as $discussion) {
//            var_dump($Discussion);
                        writeDiscussionRow($discussion, $c, $session);
                    }
                }
                ?>
                </tbody>
            </table>
        </div>
    <?php
    }

endif;
