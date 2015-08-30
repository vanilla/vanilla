<?php if (!defined('APPLICATION')) exit();

if (!function_exists('CategoryHeading')):

    /**
     * Write the category heading in a category table.
     * Good for plugins that want to override whats displayed in the heading to the category name.
     *
     * @return string
     * @since 2.1
     */
    function categoryHeading() {
        return t('Categories');
    }

endif;

if (!function_exists('CategoryPhoto')):

    /**
     *
     * @since 2.1
     */
    function categoryPhoto($Row) {
        $PhotoUrl = val('PhotoUrl', $Row);

        if ($PhotoUrl) {
            $Result = anchor(
                '<img src="'.$PhotoUrl.'" class="CategoryPhoto" alt="'.htmlspecialchars(val('Name', $Row)).'" />',
                CategoryUrl($Row, '', '//'),
                'Item-Icon PhotoWrap PhotoWrap-Category');
        } else {
            $Result = anchor(
                '<span class="sr-only">'.t('Expand for more options.').'</span>',
                CategoryUrl($Row, '', '//'),
                'Item-Icon PhotoWrap PhotoWrap-Category Hidden NoPhoto');
        }

        return $Result;
    }

endif;

if (!function_exists('CategoryString')):

    function categoryString($Rows) {
        $Result = '';
        foreach ($Rows as $Row) {
            if ($Result)
                $Result .= '<span class="Comma">, </span>';
            $Result .= anchor(htmlspecialchars($Row['Name']), $Row['Url']);
        }
        return $Result;
    }
endif;

if (!function_exists('GetOptions')):
    /**
     * Render options that the user has for this category.
     */
    function getOptions($Category) {
        if (!Gdn::session()->isValid())
            return;

        $Sender = Gdn::controller();


        $Result = '';
        $Options = '';
        $CategoryID = val('CategoryID', $Category);

        $Result = '<div class="Options">';
        $TKey = urlencode(Gdn::session()->TransientKey());

        // Mark category read.
        $Options .= '<li rel="MarkRead">'.anchor(t('Mark Read'), "/category/markread?categoryid=$CategoryID&tkey=$TKey").'</li>';

        // Follow/Unfollow category.
        if (!val('Following', $Category))
            $Options .= '<li rel="Hide">'.anchor(t('Unhide'), "/category/follow?categoryid=$CategoryID&value=1&tkey=$TKey").'</li>';
        else
            $Options .= '<li rel="Hide">'.anchor(t('Hide'), "/category/follow?categoryid=$CategoryID&value=0&tkey=$TKey").'</li>';

        // Allow plugins to add options
        $Sender->fireEvent('CategoryOptions');

        if ($Options != '') {
            $Result .= '<span class="ToggleFlyout OptionsMenu">';
            $Result .= '<span class="OptionsTitle">'.t('Options').'</span>';
            $Result .= '<span class="SpFlyoutHandle"></span>';
            $Result .= '<ul class="Flyout MenuItems">'.$Options.'</ul>';
            $Result .= '</span>';
            $Result .= '</div>';
            return $Result;
        }
    }
endif;

if (!function_exists('MostRecentString')):
    function mostRecentString($Row) {
        if (!$Row['LastTitle'])
            return '';

        $R = '';

        $R .= '<span class="MostRecent">';
        $R .= '<span class="MLabel">'.t('Most recent:').'</span> ';
        $R .= anchor(
            SliceString(Gdn_Format::text($Row['LastTitle']), 150),
            $Row['LastUrl'],
            'LatestPostTitle');

        if (val('LastName', $Row)) {
            $R .= ' ';

            $R .= '<span class="MostRecentBy">'.t('by').' ';
            $R .= userAnchor($Row, 'UserLink', 'Last');
            $R .= '</span>';
        }

        if (val('LastDateInserted', $Row)) {
            $R .= ' ';

            $R .= '<span class="MostRecentOn">';
            $R .= t('on').' ';
            $R .= anchor(
                Gdn_Format::date($Row['LastDateInserted'], 'html'),
                $Row['LastUrl'],
                'CommentDate');
            $R .= '</span>';
        }

        $R .= '</span>';

        return $R;
    }
endif;

if (!function_exists('WriteListItem')):

    function writeListItem($Row, $Depth = 1) {
        $Children = $Row['Children'];
        $WriteChildren = FALSE;
        if (!empty($Children)) {
            if (($Depth + 1) >= c('Vanilla.Categories.MaxDisplayDepth')) {
                $WriteChildren = 'list';
            } else {
                $WriteChildren = 'items';
            }
        }

        $H = 'h'.($Depth + 1);
        ?>
        <li id="Category_<?php echo $Row['CategoryID']; ?>" class="<?php echo CssClass($Row); ?>">
            <div class="ItemContent Category">
                <?php
                echo GetOptions($Row);
                echo '<'.$H.' class="CategoryName TitleWrap">';
                echo CategoryPhoto($Row);
                echo anchor(htmlspecialchars($Row['Name']), $Row['Url'], 'Title');

                Gdn::controller()->EventArguments['Category'] = $Row;
                Gdn::controller()->fireEvent('AfterCategoryTitle');
                echo '</'.$H.'>';
                ?>

                <div class="CategoryDescription">
                    <?php echo $Row['Description']; ?>
                </div>

                <?php if ($WriteChildren === 'list'): ?>
                    <div class="ChildCategories">
                        <?php
                        echo wrap(t('Child Categories').': ', 'b');
                        echo CategoryString($Children, $Depth + 1);
                        ?>
                    </div>
                <?php endif; ?>

                <div class="Meta">
            <span class="MItem RSS"><?php
                echo anchor(' ', '/categories/'.rawurlencode($Row['UrlCode']).'/feed.rss', 'SpRSS');
                ?></span>

            <span class="MItem MItem-Count DiscussionCount"><?php
                printf(PluralTranslate($Row['CountAllDiscussions'],
                    '%s discussion html', '%s discussions html', t('%s discussion'), t('%s discussions')),
                    BigPlural($Row['CountAllDiscussions'], '%s discussion'));
                ?></span>

            <span class="MItem MItem-Count CommentCount"><?php
                printf(PluralTranslate($Row['CountAllComments'],
                    '%s comment html', '%s comments html', t('%s comment'), t('%s comments')),
                    BigPlural($Row['CountAllComments'], '%s comment'));
                ?></span>

            <span class="MItem LastestPost LastDiscussionTitle"><?php
                echo MostRecentString($Row);
                ?></span>
                </div>
            </div>
        </li>
        <?php
        if ($WriteChildren === 'items') {
            foreach ($Children as $ChildRow) {
                WriteListItem($ChildRow, $Depth + 1);
            }
        }
    }
endif;

if (!function_exists('WriteTableHead')):

    function writeTableHead() {
        ?>
        <tr>
            <td class="CategoryName">
                <div class="Wrap"><?php echo CategoryHeading(); ?></div>
            </td>
            <td class="BigCount CountDiscussions">
                <div class="Wrap"><?php echo t('Discussions'); ?></div>
            </td>
            <td class="BigCount CountComments">
                <div class="Wrap"><?php echo t('Comments'); ?></div>
            </td>
            <td class="BlockColumn LatestPost">
                <div class="Wrap"><?php echo t('Latest Post'); ?></div>
            </td>
        </tr>
    <?php
    }
endif;

if (!function_exists('WriteTableRow')):

    function writeTableRow($Row, $Depth = 1) {
        $Children = $Row['Children'];
        $WriteChildren = FALSE;
        if (!empty($Children)) {
            if (($Depth + 1) >= c('Vanilla.Categories.MaxDisplayDepth')) {
                $WriteChildren = 'list';
            } else {
                $WriteChildren = 'rows';
            }
        }

        $H = 'h'.($Depth + 1);
        ?>
        <tr class="<?php echo CssClass($Row); ?>">
            <td class="CategoryName">
                <div class="Wrap">
                    <?php
                    echo GetOptions($Row);

                    echo CategoryPhoto($Row);

                    echo "<{$H}>";
                    echo anchor(htmlspecialchars($Row['Name']), $Row['Url']);
                    Gdn::controller()->EventArguments['Category'] = $Row;
                    Gdn::controller()->fireEvent('AfterCategoryTitle');
                    echo "</{$H}>";
                    ?>
                    <div class="CategoryDescription">
                        <?php echo $Row['Description']; ?>
                    </div>
                    <?php if ($WriteChildren === 'list'): ?>
                        <div class="ChildCategories">
                            <?php
                            echo wrap(t('Child Categories').': ', 'b');
                            echo CategoryString($Children, $Depth + 1);
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
            </td>
            <td class="BigCount CountDiscussions">
                <div class="Wrap">
                    <?php
                    //            echo "({$Row['CountDiscussions']})";
                    echo BigPlural($Row['CountAllDiscussions'], '%s discussion');
                    ?>
                </div>
            </td>
            <td class="BigCount CountComments">
                <div class="Wrap">
                    <?php
                    //            echo "({$Row['CountComments']})";
                    echo BigPlural($Row['CountAllComments'], '%s comment');
                    ?>
                </div>
            </td>
            <td class="BlockColumn LatestPost">
                <div class="Block Wrap">
                    <?php if ($Row['LastTitle']): ?>
                        <?php
                        echo userPhoto($Row, array('Size' => 'Small', 'Px' => 'Last'));
                        echo anchor(
                            SliceString(Gdn_Format::text($Row['LastTitle']), 100),
                            $Row['LastUrl'],
                            'BlockTitle LatestPostTitle',
                            array('title' => html_entity_decode($Row['LastTitle'])));
                        ?>
                        <div class="Meta">
                            <?php
                            echo userAnchor($Row, 'UserLink MItem', 'Last');
                            ?>
                            <span class="Bullet">•</span>
                            <?php
                            echo anchor(
                                Gdn_Format::date($Row['LastDateInserted'], 'html'),
                                $Row['LastUrl'],
                                'CommentDate MItem');

                            if (isset($Row['LastCategoryID'])) {
                                $LastCategory = CategoryModel::categories($Row['LastCategoryID']);

                                echo ' <span>',
                                sprintf('in %s', anchor($LastCategory['Name'], CategoryUrl($LastCategory, '', '//'))),
                                '</span>';

                            }
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php
        if ($WriteChildren === 'rows') {
            foreach ($Children as $ChildRow) {
                WriteTableRow($ChildRow, $Depth + 1);
            }
        }
    }
endif;

if (!function_exists('WriteCategoryList')):

    function writeCategoryList($Categories, $Depth = 1) {
        ?>
        <div class="DataListWrap">
            <ul class="DataList CategoryList">
                <?php
                foreach ($Categories as $Category) {
                    WriteListItem($Category, $Depth);
                }
                ?>
            </ul>
        </div>
    <?php
    }
endif;

if (!function_exists('WriteCategoryTable')):

    function writeCategoryTable($Categories, $Depth = 1) {
        ?>
        <div class="DataTableWrap">
            <table class="DataTable CategoryTable">
                <thead>
                <?php
                WriteTableHead();
                ?>
                </thead>
                <tbody>
                <?php
                foreach ($Categories as $Category) {
                    WriteTableRow($Category, $Depth);
                }
                ?>
                </tbody>
            </table>
        </div>
    <?php
    }
endif;
