<?php if (!defined('APPLICATION')) return;

// If email is disabled, do not show those options.
$emailClass = (c('Garden.Email.Disabled')) ? ' Hidden' : '';
$span = (c('Garden.Email.Disabled')) ? '1' : '2';

?>
<h2><?php echo t('Category Notifications'); ?></h2>
<div class="DismissMessage InfoMessage">
    <?php
    echo t('You can follow individual categories and be notified of all posts within them.');
    ?>
</div>
<table class="PreferenceGroup">
    <colgroup></colgroup>
    <colgroup span="2"></colgroup>
    <colgroup span="2"></colgroup>
    <thead>
    <tr>
        <th scope="col" style="border: none;">&nbsp;</th>
        <th id="DiscussionsNotificationHeader" class="TopHeading" colspan="<?php echo $span; ?>" scope="col">
            <?php echo t('Discussions'); ?>
        </th>
        <th id="CommentsNotificationHeader" class="TopHeading" colspan="<?php echo $span; ?>" scope="col">
            <?php echo t('Comments'); ?>
        </th>
    </tr>
    <tr>
        <th id="CategoryNotificationHeader" scope="col" style="text-align: left;"><?php echo t('Category'); ?></th>
        <th id="EmailDiscussionsHeader" class="PrefCheckBox BottomHeading<?php echo $emailClass; ?>" scope="col">
            <?php echo t('Email'); ?>
        </th>
        <th id="PopupDiscussionsHeader" class="PrefCheckBox BottomHeading" scope="col">
            <?php echo t('Popup'); ?>
        </th>
        <th id="EmailCommentsHeader" class="PrefCheckBox BottomHeading<?php echo $emailClass; ?>" scope="col">
            <?php echo t('Email'); ?>
        </th>
        <th id="PopupCommentsHeader" class="PrefCheckBox BottomHeading" scope="col">
            <?php echo t('Popup'); ?>
        </th>
    </tr>
    </thead>
    <tbody>
    <?php
    foreach (Gdn::controller()->data('CategoryNotifications') as $Category):
        $CategoryID = $Category['CategoryID'];

        if ($Category['Heading']):
            ?>
            <tr>
                <th>
                    <b><?php echo $Category['Name']; ?></b>
                </th>
                <th colspan="4">
                    &#160;
                </th>
            </tr>
        <?php else: ?>
            <tr>
                <td class="<?php echo "Depth_{$Category['Depth']}"; ?>" headers="CategoryNotificationHeader">
                    <?php echo $Category['Name']; ?>
                </td>
                <td class="PrefCheckBox<?php echo $emailClass; ?>" headers="DiscussionsNotificationHeader EmailDiscussionsHeader">
                    <?php echo Gdn::controller()->Form->checkBox("Email.NewDiscussion.{$CategoryID}", '', ['value' => 1]); ?>
                </td>
                <td class="PrefCheckBox" headers="DiscussionsNotificationHeader PopupDiscussionsHeader">
                    <?php echo Gdn::controller()->Form->checkBox("Popup.NewDiscussion.{$CategoryID}", '', ['value' => 1]); ?>
                </td>
                <td class="PrefCheckBox<?php echo $emailClass; ?>" headers="CommentsNotificationHeader EmailCommentsHeader">
                    <?php echo Gdn::controller()->Form->checkBox("Email.NewComment.{$CategoryID}", '', ['value' => 1]); ?>
                </td>
                <td class="PrefCheckBox" headers="CommentsNotificationHeader EmailCommentsHeader">
                    <?php echo Gdn::controller()->Form->checkBox("Popup.NewComment.{$CategoryID}", '', ['value' => 1]); ?>
                </td>
            </tr>
        <?php
        endif;
    endforeach;
    ?>
    </tbody>
</table>
