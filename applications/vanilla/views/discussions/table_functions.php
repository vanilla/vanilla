<?php if (!defined('APPLICATION')) exit();

if (!function_exists('WriteDiscussionHeading')) :
    /**
     * Write the table heading.
     */
    function writeDiscussionHeading() {
        ?>
        <tr>
            <?php echo AdminCheck(NULL, ['<td class="CheckBoxColumn"><div class="Wrap">', '</div></td>']); ?>
            <td class="DiscussionName">
                <div class="Wrap"><?php echo DiscussionHeading() ?></div>
            </td>
            <td class="BlockColumn BlockColumn-User FirstUser">
                <div class="Wrap"><?php echo t('Started By'); ?></div>
            </td>
            <td class="BigCount CountReplies">
                <div class="Wrap"><?php echo t('Replies'); ?></div>
            </td>
            <td class="BigCount CountViews">
                <div class="Wrap"><?php echo t('Views'); ?></div>
            </td>
            <td class="BlockColumn BlockColumn-User LastUser">
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
        $cssClass = CssClass($discussion);
        $discussionUrl = $discussion->Url;

        if ($session->UserID) {
            $discussionUrl .= '#latest';
        }
        $sender->EventArguments['DiscussionUrl'] = &$discussionUrl;
        $sender->EventArguments['Discussion'] = &$discussion;
        $sender->EventArguments['CssClass'] = &$cssClass;

        $first = UserBuilder($discussion, 'First');
        if ($discussion->LastUserID) {
            $last = UserBuilder($discussion, 'Last');
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

        $firstPageUrl = DiscussionUrl($discussion, 1);
        $lastPageUrl = DiscussionUrl($discussion, val('CountPages', $discussion)).'#latest';
        ?>
        <tr id="Discussion_<?php echo $discussion->DiscussionID; ?>" class="<?php echo $cssClass; ?>">
            <?php $sender->fireEvent('BeforeDiscussionContent'); ?>
            <?php echo AdminCheck($discussion, ['<td class="CheckBoxColumn"><div class="Wrap">', '</div></td>']); ?>
            <td class="DiscussionName">
                <div class="Wrap">
         <span class="Options">
            <?php
            echo OptionsList($discussion);
            echo BookmarkButton($discussion);
            ?>
         </span>
                    <?php
                    $sender->fireEvent('BeforeDiscussionTitle');
                    echo anchor($discussionName, $discussionUrl, 'Title').' ';
                    $sender->fireEvent('AfterDiscussionTitle');

                    WriteMiniPager($discussion);
                    echo NewComments($discussion);
                    if ($sender->data('_ShowCategoryLink', true)) {
                        echo CategoryLink($discussion, ' '.t('in').' ');
                    }
                    // Other stuff that was in the standard view that you may want to display:
                    echo '<div class="Meta Meta-Discussion">';
                    WriteTags($discussion);
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
                    echo BigPlural($discussion->CountComments, '%s comment');
                    ?>
                </div>
            </td>
            <td class="BigCount CountViews">
                <div class="Wrap">
                    <?php
                    // Exact Number
                    // echo number_format($Discussion->CountViews);

                    // Round Number
                    echo BigPlural($discussion->CountViews, '%s view');
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
            <table class="DataTable DiscussionsTable">
                <thead>
                <?php
                WriteDiscussionHeading();
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
