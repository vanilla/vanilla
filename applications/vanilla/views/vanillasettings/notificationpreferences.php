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
    <thead>
    <tr>
        <th style="border: none;">&nbsp;</th>
        <th class="TopHeading" colspan="<?php echo $span; ?>"><?php echo t('Discussions'); ?></th>
        <th class="TopHeading" colspan="<?php echo $span; ?>"><?php echo t('Comments'); ?></th>
    </tr>
    <tr>
        <th style="text-align: left;"><?php echo t('Category'); ?></th>
        <th class="PrefCheckBox BottomHeading<?php echo $emailClass; ?>"><?php echo t('Email'); ?></th>
        <th class="PrefCheckBox BottomHeading"><?php echo t('Popup'); ?></th>
        <th class="PrefCheckBox BottomHeading<?php echo $emailClass; ?>"><?php echo t('Email'); ?></th>
        <th class="PrefCheckBox BottomHeading"><?php echo t('Popup'); ?></th>
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
                <td class="<?php echo "Depth_{$Category['Depth']}"; ?>"><?php echo $Category['Name']; ?></td>
                <td class="PrefCheckBox<?php echo $emailClass; ?>"><?php echo Gdn::controller()->Form->checkBox("Email.NewDiscussion.{$CategoryID}", '', array('value' => 1)); ?></td>
                <td class="PrefCheckBox"><?php echo Gdn::controller()->Form->checkBox("Popup.NewDiscussion.{$CategoryID}", '', array('value' => 1)); ?></td>
                <td class="PrefCheckBox<?php echo $emailClass; ?>"><?php echo Gdn::controller()->Form->checkBox("Email.NewComment.{$CategoryID}", '', array('value' => 1)); ?></td>
                <td class="PrefCheckBox"><?php echo Gdn::controller()->Form->checkBox("Popup.NewComment.{$CategoryID}", '', array('value' => 1)); ?></td>
            </tr>
        <?php
        endif;
    endforeach;
    ?>
    </tbody>
</table>
