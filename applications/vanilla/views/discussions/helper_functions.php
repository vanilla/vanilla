<?php if (!defined('APPLICATION')) exit();

if (!function_exists('AdminCheck')) {
    /**
     *
     *
     * @param null $Discussion
     * @param bool|FALSE $Wrap
     * @return string
     */
    function adminCheck($Discussion = null, $Wrap = FALSE) {
        static $UseAdminChecks = NULL;
        if ($UseAdminChecks === null) {
            $UseAdminChecks = c('Vanilla.AdminCheckboxes.Use') && Gdn::session()->checkPermission('Garden.Moderation.Manage');
        }
        if (!$UseAdminChecks) {
            return '';
        }

        static $CanEdits = array(), $Checked = NULL;
        $Result = '';

        if ($Discussion) {
            if (!isset($CanEdits[$Discussion->CategoryID])) {
                $CanEdits[$Discussion->CategoryID] = val('PermsDiscussionsEdit', CategoryModel::categories($Discussion->CategoryID));
            }

            if ($CanEdits[$Discussion->CategoryID]) {
                // Grab the list of currently checked discussions.
                if ($Checked === null) {
                    $Checked = (array)Gdn::session()->getAttribute('CheckedDiscussions', array());

                    if (!is_array($Checked)) {
                        $Checked = array();
                    }
                }

                if (in_array($Discussion->DiscussionID, $Checked))
                    $ItemSelected = ' checked="checked"';
                else
                    $ItemSelected = '';

                $Result = <<<EOT
<span class="AdminCheck"><input type="checkbox" name="DiscussionID[]" value="{$Discussion->DiscussionID}" $ItemSelected /></span>
EOT;
            }
        } else {
            $Result = '<span class="AdminCheck"><input type="checkbox" name="Toggle" /></span>';
        }

        if ($Wrap) {
            $Result = $Wrap[0].$Result.$Wrap[1];
        }

        return $Result;
    }
}

if (!function_exists('BookmarkButton')) {
    /**
     *
     *
     * @param $Discussion
     * @return string
     */
    function bookmarkButton($Discussion) {
        if (!Gdn::session()->isValid()) {
            return '';
        }

        // Bookmark link
        $Title = t($Discussion->Bookmarked == '1' ? 'Unbookmark' : 'Bookmark');
        return anchor(
            $Title,
            '/discussion/bookmark/'.$Discussion->DiscussionID.'/'.Gdn::session()->TransientKey(),
            'Hijack Bookmark'.($Discussion->Bookmarked == '1' ? ' Bookmarked' : ''),
            array('title' => $Title)
        );
    }
}

if (!function_exists('CategoryLink')) :
    /**
     *
     *
     * @param $Discussion
     * @param string $Prefix
     * @return string
     */
    function categoryLink($Discussion, $Prefix = ' ') {
        $Category = CategoryModel::categories(val('CategoryID', $Discussion));
        if ($Category) {
            return wrap($Prefix.anchor(htmlspecialchars($Category['Name']), $Category['Url']), 'span', array('class' => 'MItem Category'));
        }
    }

endif;

if (!function_exists('DiscussionHeading')) :
    /**
     *
     *
     * @return string
     */
    function discussionHeading() {
        return t('Discussion');
    }

endif;

if (!function_exists('WriteDiscussion')) :
    /**
     *
     *
     * @param $Discussion
     * @param $Sender
     * @param $Session
     */
    function writeDiscussion($Discussion, $Sender, $Session) {
        $CssClass = CssClass($Discussion);
        $DiscussionUrl = $Discussion->Url;
        $Category = CategoryModel::categories($Discussion->CategoryID);

        if ($Session->UserID) {
            $DiscussionUrl .= '#latest';
        }
        $Sender->EventArguments['DiscussionUrl'] = &$DiscussionUrl;
        $Sender->EventArguments['Discussion'] = &$Discussion;
        $Sender->EventArguments['CssClass'] = &$CssClass;

        $First = UserBuilder($Discussion, 'First');
        $Last = UserBuilder($Discussion, 'Last');
        $Sender->EventArguments['FirstUser'] = &$First;
        $Sender->EventArguments['LastUser'] = &$Last;

        $Sender->fireEvent('BeforeDiscussionName');

        $DiscussionName = $Discussion->Name;
        if ($DiscussionName == '') {
            $DiscussionName = t('Blank Discussion Topic');
        }
        $Sender->EventArguments['DiscussionName'] = &$DiscussionName;

        static $FirstDiscussion = true;
        if (!$FirstDiscussion) {
            $Sender->fireEvent('BetweenDiscussion');
        } else {
            $FirstDiscussion = false;
        }

        $Discussion->CountPages = ceil($Discussion->CountComments / $Sender->CountCommentsPerPage);
        ?>
        <li id="Discussion_<?php echo $Discussion->DiscussionID; ?>" class="<?php echo $CssClass; ?>">
            <?php
            if (!property_exists($Sender, 'CanEditDiscussions')) {
                $Sender->CanEditDiscussions = val('PermsDiscussionsEdit', CategoryModel::categories($Discussion->CategoryID)) && c('Vanilla.AdminCheckboxes.Use');
            }
            $Sender->fireEvent('BeforeDiscussionContent');
            ?>
            <span class="Options">
      <?php
      echo optionsList($Discussion);
      echo bookmarkButton($Discussion);
      ?>
   </span>

            <div class="ItemContent Discussion">
                <div class="Title">
                    <?php
                    echo adminCheck($Discussion, array('', ' ')).anchor($DiscussionName, $DiscussionUrl);
                    $Sender->fireEvent('AfterDiscussionTitle');
                    ?>
                </div>
                <div class="Meta Meta-Discussion">
                    <?php
                    writeTags($Discussion);
                    ?>
                    <span class="MItem MCount ViewCount"><?php
                        printf(pluralTranslate($Discussion->CountViews,
                            '%s view html', '%s views html', t('%s view'), t('%s views')),
                            bigPlural($Discussion->CountViews, '%s view'));
                        ?></span>
         <span class="MItem MCount CommentCount"><?php
             printf(pluralTranslate($Discussion->CountComments,
                 '%s comment html', '%s comments html', t('%s comment'), t('%s comments')),
                 bigPlural($Discussion->CountComments, '%s comment'));
             ?></span>
         <span class="MItem MCount DiscussionScore Hidden"><?php
             $Score = $Discussion->Score;
             if ($Score == '') $Score = 0;
             printf(plural($Score,
                 '%s point', '%s points',
                 bigPlural($Score, '%s point')));
             ?></span>
                    <?php
                    echo newComments($Discussion);

                    $Sender->fireEvent('AfterCountMeta');

                    if ($Discussion->LastCommentID != '') {
                        echo ' <span class="MItem LastCommentBy">'.sprintf(t('Most recent by %1$s'), userAnchor($Last)).'</span> ';
                        echo ' <span class="MItem LastCommentDate">'.Gdn_Format::date($Discussion->LastDate, 'html').'</span>';
                    } else {
                        echo ' <span class="MItem LastCommentBy">'.sprintf(t('Started by %1$s'), userAnchor($First)).'</span> ';
                        echo ' <span class="MItem LastCommentDate">'.Gdn_Format::date($Discussion->FirstDate, 'html');
                        if ($Source = val('Source', $Discussion)) {
                            echo ' '.sprintf(t('via %s'), t($Source.' Source', $Source));
                        }
                        echo '</span> ';
                    }

                    if ($Sender->data('_ShowCategoryLink', true) && c('Vanilla.Categories.Use') && $Category) {
                        echo wrap(
                            anchor(htmlspecialchars($Discussion->Category),
                            CategoryUrl($Discussion->CategoryUrlCode)),
                            'span',
                            array('class' => 'MItem Category '.$Category['CssClass'])
                        );
                    }
                    $Sender->fireEvent('DiscussionMeta');
                    ?>
                </div>
            </div>
            <?php $Sender->fireEvent('AfterDiscussionContent'); ?>
        </li>
    <?php
    }
endif;

if (!function_exists('WriteDiscussionSorter')) :
    /**
     *
     *
     * @param null $Selected
     * @param null $Options
     */
    function writeDiscussionSorter($Selected = null, $Options = null) {
        deprecated('writeDiscussionSorter', 'DiscussionSortFilterModule', 'March 2016');

        if ($Selected === null) {
            $Selected = Gdn::session()->getPreference('Discussions.SortField', 'DateLastComment');
        }
        $Selected = stringBeginsWith($Selected, 'd.', TRUE, true);

        $Options = array(
            'DateLastComment' => t('Sort by Last Comment', 'by Last Comment'),
            'DateInserted' => t('Sort by Start Date', 'by Start Date')
        );

        ?>
        <span class="ToggleFlyout SelectFlyout">
        <?php
        if (isset($Options[$Selected])) {
            $Text = $Options[$Selected];
        } else {
            $Text = reset($Options);
        }
        echo wrap($Text.' '.sprite('', 'DropHandle'), 'span', array('class' => 'Selected'));
        ?>
            <div class="Flyout MenuItems">
                <ul>
                    <?php
                    foreach ($Options as $SortField => $SortText) {
                        echo wrap(Anchor($SortText, '#', array('class' => 'SortDiscussions', 'data-field' => $SortField)), 'li');
                    }
                    ?>
                </ul>
            </div>
         </span>
        <?php
    }
endif;

if (!function_exists('WriteMiniPager')) :
    /**
     *
     *
     * @param $Discussion
     */
    function writeMiniPager($Discussion) {
        if (!property_exists($Discussion, 'CountPages')) {
            return;
        }

        if ($Discussion->CountPages > 1) {
            echo '<span class="MiniPager">';
            if ($Discussion->CountPages < 5) {
                for ($i = 0; $i < $Discussion->CountPages; $i++) {
                    WritePageLink($Discussion, $i + 1);
                }
            } else {
                WritePageLink($Discussion, 1);
                WritePageLink($Discussion, 2);
                echo '<span class="Elipsis">...</span>';
                WritePageLink($Discussion, $Discussion->CountPages - 1);
                WritePageLink($Discussion, $Discussion->CountPages);
                // echo anchor('Go To Page', '#', 'GoToPageLink');
            }
            echo '</span>';
        }
    }
endif;

if (!function_exists('WritePageLink')):
    /**
     *
     *
     * @param $Discussion
     * @param $PageNumber
     */
    function writePageLink($Discussion, $PageNumber) {
        echo anchor($PageNumber, DiscussionUrl($Discussion, $PageNumber));
    }
endif;

if (!function_exists('NewComments')) :
    /**
     *
     *
     * @param $Discussion
     * @return string
     */
    function newComments($Discussion) {
        if (!Gdn::session()->isValid())
            return '';

        if ($Discussion->CountUnreadComments === TRUE) {
            $Title = htmlspecialchars(t("You haven't read this yet."));

            return ' <strong class="HasNew JustNew NewCommentCount" title="'.$Title.'">'.t('new discussion', 'new').'</strong>';
        } elseif ($Discussion->CountUnreadComments > 0) {
            $Title = htmlspecialchars(plural($Discussion->CountUnreadComments, "%s new comment since you last read this.", "%s new comments since you last read this."));

            return ' <strong class="HasNew NewCommentCount" title="'.$Title.'">'.plural($Discussion->CountUnreadComments, '%s new', '%s new plural', bigPlural($Discussion->CountUnreadComments, '%s new', '%s new plural')).'</strong>';
        }
        return '';
    }
endif;

if (!function_exists('tag')) :
    /**
     *
     *
     * @param $Discussion
     * @param $Column
     * @param $Code
     * @param bool|false $CssClass
     * @return string|void
     */
    function tag($Discussion, $Column, $Code, $CssClass = FALSE) {
        $Discussion = (object)$Discussion;

        if (is_numeric($Discussion->$Column) && !$Discussion->$Column)
            return '';
        if (!is_numeric($Discussion->$Column) && strcasecmp($Discussion->$Column, $Code) != 0)
            return;

        if (!$CssClass)
            $CssClass = "Tag-$Code";

        return ' <span class="Tag '.$CssClass.'" title="'.htmlspecialchars(t($Code)).'">'.t($Code).'</span> ';
    }
endif;

if (!function_exists('writeTags')) :
    /**
     *
     *
     * @param $Discussion
     * @throws Exception
     */
    function writeTags($Discussion) {
        Gdn::controller()->fireEvent('BeforeDiscussionMeta');

        echo Tag($Discussion, 'Announce', 'Announcement');
        echo Tag($Discussion, 'Closed', 'Closed');

        Gdn::controller()->fireEvent('AfterDiscussionLabels');
    }
endif;

if (!function_exists('writeFilterTabs')) :
    /**
     *
     *
     * @param $Sender
     */
    function writeFilterTabs($Sender) {
        $Session = Gdn::session();
        $Title = property_exists($Sender, 'Category') ? val('Name', $Sender->Category, '') : '';
        if ($Title == '') {
            $Title = t('All Discussions');
        }
        $Bookmarked = t('My Bookmarks');
        $MyDiscussions = t('My Discussions');
        $MyDrafts = t('My Drafts');
        $CountBookmarks = 0;
        $CountDiscussions = 0;
        $CountDrafts = 0;

        if ($Session->isValid()) {
            $CountBookmarks = $Session->User->CountBookmarks;
            $CountDiscussions = $Session->User->CountDiscussions;
            $CountDrafts = $Session->User->CountDrafts;
        }

        if (c('Vanilla.Discussions.ShowCounts', true)) {
            $Bookmarked .= countString($CountBookmarks, url('/discussions/UserBookmarkCount'));
            $MyDiscussions .= countString($CountDiscussions);
            $MyDrafts .= countString($CountDrafts);
        }

        ?>
        <div class="Tabs DiscussionsTabs">
            <?php
            if (!property_exists($Sender, 'CanEditDiscussions')) {
                $Sender->CanEditDiscussions = $Session->checkPermission('Vanilla.Discussions.Edit', true, 'Category', 'any') && c('Vanilla.AdminCheckboxes.Use');
            }
            if ($Sender->CanEditDiscussions) {
                ?>
                <span class="Options"><span class="AdminCheck">
                    <input type="checkbox" name="Toggle"/>
                </span></span>
            <?php } ?>
            <ul>
                <?php $Sender->fireEvent('BeforeDiscussionTabs'); ?>
                <li<?php echo strtolower($Sender->ControllerName) == 'discussionscontroller' && strtolower($Sender->RequestMethod) == 'index' ? ' class="Active"' : ''; ?>><?php echo anchor(t('All Discussions'), 'discussions', 'TabLink'); ?></li>
                <?php $Sender->fireEvent('AfterAllDiscussionsTab'); ?>

                <?php
                if (c('Vanilla.Categories.ShowTabs')) {
                    $CssClass = '';
                    if (strtolower($Sender->ControllerName) == 'categoriescontroller' && strtolower($Sender->RequestMethod) == 'all') {
                        $CssClass = 'Active';
                    }

                    echo " <li class=\"$CssClass\">".anchor(t('Categories'), '/categories/all', 'TabLink').'</li> ';
                }
                ?>
                <?php if ($CountBookmarks > 0 || $Sender->RequestMethod == 'bookmarked') { ?>
                    <li<?php echo $Sender->RequestMethod == 'bookmarked' ? ' class="Active"' : ''; ?>><?php echo anchor($Bookmarked, '/discussions/bookmarked', 'MyBookmarks TabLink'); ?></li>
                    <?php
                    $Sender->fireEvent('AfterBookmarksTab');
                }
                if (($CountDiscussions > 0 || $Sender->RequestMethod == 'mine') && c('Vanilla.Discussions.ShowMineTab', true)) {
                    ?>
                    <li<?php echo $Sender->RequestMethod == 'mine' ? ' class="Active"' : ''; ?>><?php echo anchor($MyDiscussions, '/discussions/mine', 'MyDiscussions TabLink'); ?></li>
                <?php
                }
                if ($CountDrafts > 0 || $Sender->ControllerName == 'draftscontroller') {
                    ?>
                    <li<?php echo $Sender->ControllerName == 'draftscontroller' ? ' class="Active"' : ''; ?>><?php echo anchor($MyDrafts, '/drafts', 'MyDrafts TabLink'); ?></li>
                <?php
                }
                $Sender->fireEvent('AfterDiscussionTabs');
                ?>
            </ul>
        </div>
    <?php
    }
endif;

if (!function_exists('optionsList')) :
    /**
     * Build HTML for discussions options menu.
     *
     * @param $discussion
     * @return DropdownModule|string
     * @throws Exception
     */
    function optionsList($discussion) {
        if (Gdn::session()->isValid() && !empty(Gdn::controller()->ShowOptions)) {
            include_once Gdn::controller()->fetchViewLocation('helper_functions', 'discussion', 'vanilla');
            return getDiscussionOptionsDropdown($discussion);
        }
        return '';
    }
endif;

if (!function_exists('writeOptions')) :
    /**
     * Render options that the user has for this discussion.
     */
    function writeOptions($Discussion) {
        if (!Gdn::session()->isValid() || !Gdn::controller()->ShowOptions)
            return;

        echo '<span class="Options">';

        // Options list.
        echo optionsList($Discussion);

        // Bookmark button.
        echo bookmarkButton($Discussion);

        // Admin check.
        echo adminCheck($Discussion);

        echo '</span>';
    }
endif;
