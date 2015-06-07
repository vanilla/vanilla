<?php if (!defined('APPLICATION')) exit();

if (!function_exists('WriteDiscussionHeading')):

    function writeDiscussionHeading() {
        ?>
        <tr>
            <?php echo AdminCheck(NULL, array('<td class="CheckBoxColumn"><div class="Wrap">', '</div></td>')); ?>
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

if (!function_exists('WriteDiscussionRow')):

    /**
     * Writes a discussion in table row format.
     */
    function writeDiscussionRow($Discussion, &$Sender, &$Session, $Alt2) {
        if (!property_exists($Sender, 'CanEditDiscussions'))
            $Sender->CanEditDiscussions = val('PermsDiscussionsEdit', CategoryModel::categories($Discussion->CategoryID)) && c('Vanilla.AdminCheckboxes.Use');

        $CssClass = CssClass($Discussion);
        $DiscussionUrl = $Discussion->Url;

        if ($Session->UserID)
            $DiscussionUrl .= '#latest';

        $Sender->EventArguments['DiscussionUrl'] = &$DiscussionUrl;
        $Sender->EventArguments['Discussion'] = &$Discussion;
        $Sender->EventArguments['CssClass'] = &$CssClass;

        $First = UserBuilder($Discussion, 'First');
        if ($Discussion->LastUserID)
            $Last = UserBuilder($Discussion, 'Last');
        else {
            $Last = $First;
        }
        $Sender->EventArguments['FirstUser'] = &$First;
        $Sender->EventArguments['LastUser'] = &$Last;

        $Sender->fireEvent('BeforeDiscussionName');

        $DiscussionName = $Discussion->Name;
        if ($DiscussionName == '')
            $DiscussionName = t('Blank Discussion Topic');

        $Sender->EventArguments['DiscussionName'] = &$DiscussionName;
        $Discussion->CountPages = ceil($Discussion->CountComments / $Sender->CountCommentsPerPage);

        $FirstPageUrl = DiscussionUrl($Discussion, 1);
        $LastPageUrl = DiscussionUrl($Discussion, val('CountPages', $Discussion)).'#latest';
        ?>
        <tr id="Discussion_<?php echo $Discussion->DiscussionID; ?>" class="<?php echo $CssClass; ?>">
            <?php $Sender->fireEvent('BeforeDiscussionContent'); ?>
            <?php echo AdminCheck($Discussion, array('<td class="CheckBoxColumn"><div class="Wrap">', '</div></td>')); ?>
            <td class="DiscussionName">
                <div class="Wrap">
         <span class="Options">
            <?php
            echo OptionsList($Discussion);
            echo BookmarkButton($Discussion);
            ?>
         </span>
                    <?php

                    echo anchor($DiscussionName, $DiscussionUrl, 'Title').' ';
                    $Sender->fireEvent('AfterDiscussionTitle');

                    WriteMiniPager($Discussion);
                    echo NewComments($Discussion);
                    if ($Sender->data('_ShowCategoryLink', true))
                        echo CategoryLink($Discussion, ' '.t('in').' ');

                    // Other stuff that was in the standard view that you may want to display:
                    echo '<div class="Meta Meta-Discussion">';
                    WriteTags($Discussion);
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
                    echo userPhoto($First, array('Size' => 'Small'));
                    echo userAnchor($First, 'UserLink BlockTitle');
                    echo '<div class="Meta">';
                    echo anchor(Gdn_Format::date($Discussion->FirstDate, 'html'), $FirstPageUrl, 'CommentDate MItem');
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
                    echo BigPlural($Discussion->CountComments, '%s comment');
                    ?>
                </div>
            </td>
            <td class="BigCount CountViews">
                <div class="Wrap">
                    <?php
                    // Exact Number
                    // echo number_format($Discussion->CountViews);

                    // Round Number
                    echo BigPlural($Discussion->CountViews, '%s view');
                    ?>
                </div>
            </td>
            <td class="BlockColumn BlockColumn-User LastUser">
                <div class="Block Wrap">
                    <?php
                    if ($Last) {
                        echo userPhoto($Last, array('Size' => 'Small'));
                        echo userAnchor($Last, 'UserLink BlockTitle');
                        echo '<div class="Meta">';
                        echo anchor(Gdn_Format::date($Discussion->LastDate, 'html'), $LastPageUrl, 'CommentDate MItem');
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
                $Session = Gdn::session();
                $Alt = '';
                $Announcements = $c->data('Announcements');
                if (is_a($Announcements, 'Gdn_DataSet')) {
                    foreach ($Announcements->result() as $Discussion) {
                        $Alt = $Alt == ' Alt' ? '' : ' Alt';
                        WriteDiscussionRow($Discussion, $c, $Session, $Alt);
                    }
                }

                $Alt = '';
                $Discussions = $c->data('Discussions');
                if (is_a($Discussions, 'Gdn_DataSet')) {
                    foreach ($Discussions->result() as $Discussion) {
                        $Alt = $Alt == ' Alt' ? '' : ' Alt';
//            var_dump($Discussion);
                        WriteDiscussionRow($Discussion, $c, $Session, $Alt);
                    }
                }
                ?>
                </tbody>
            </table>
        </div>
    <?php
    }

endif;
