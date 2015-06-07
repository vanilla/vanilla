<?php if (!defined('APPLICATION')) return; ?>
<h2><?php echo t('Category Notifications'); ?></h2>
<div class="DismissMessage InfoMessage">
    <?php
    echo t('You can follow individual categories and be notified of all posts within them.');
    ?>
</div>
<table class="PreferenceGroup">
    <thead>
    <tr>

        <td style="border: none;">&nbsp;</td>
        <td class="TopHeading" colspan="2"><?php echo t('Discussions'); ?></td>
        <td class="TopHeading" colspan="2"><?php echo t('Comments'); ?></td>
    </tr>
    <tr>
        <td style="text-align: left;"><?php echo t('Category'); ?></td>
        <td class="PrefCheckBox BottomHeading"><?php echo t('Email'); ?></td>
        <td class="PrefCheckBox BottomHeading"><?php echo t('Popup'); ?></td>
        <td class="PrefCheckBox BottomHeading"><?php echo t('Email'); ?></td>
        <td class="PrefCheckBox BottomHeading"><?php echo t('Popup'); ?></td>
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
                <td class="PrefCheckBox"><?php echo Gdn::controller()->Form->CheckBox("Email.NewDiscussion.{$CategoryID}", '', array('value' => 1)); ?></td>
                <td class="PrefCheckBox"><?php echo Gdn::controller()->Form->CheckBox("Popup.NewDiscussion.{$CategoryID}", '', array('value' => 1)); ?></td>
                <td class="PrefCheckBox"><?php echo Gdn::controller()->Form->CheckBox("Email.NewComment.{$CategoryID}", '', array('value' => 1)); ?></td>
                <td class="PrefCheckBox"><?php echo Gdn::controller()->Form->CheckBox("Popup.NewComment.{$CategoryID}", '', array('value' => 1)); ?></td>
            </tr>
        <?php
        endif;
    endforeach;
    ?>
    </tbody>
</table>
