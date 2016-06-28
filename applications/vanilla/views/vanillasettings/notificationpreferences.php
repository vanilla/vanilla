<?php if (!defined('APPLICATION')) return;

if ($Sender->data('NoEmail')) {
    $emailEnabled = false;
    $colspan = ' colspan="2" ';
} else {
    $emailEnabled = true;
    $colspan = null;
}
?>
asdfdsgssdgsgs
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
        <?php
        for($i = 0; $i < 2; $i++) {
            if ($emailEnabled) {
                echo '<td class="PrefCheckBox BottomHeading">'.t('Email').'</td>';
            }
            echo '<td class="PrefCheckBox BottomHeading"'.$colspan.'>'.t('Popup').'</td>';

        }
        ?>
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
                <?php
                    $checkboxeIDs = [];
                    if ($emailEnabled) {
                        $checkboxeIDs[] = "Email.NewDiscussion.$CategoryID";
                    }
                    $checkboxeIDs[] = "Popup.NewDiscussion.$CategoryID";
                    if ($emailEnabled) {
                        $checkboxeIDs[] = "Email.NewComment.$CategoryID";
                    }
                    $checkboxeIDs[] = "Popup.NewComment.$CategoryID";


                    foreach($checkboxeIDs as $checkboxID) {
                        echo '<td class="PrefCheckBox"'.$colspan.'>'.Gdn::controller()->Form->CheckBox($checkboxID, '', array('value' => 1)).'</td>';
                    }
                ?>

            </tr>
        <?php
        endif;
    endforeach;
    ?>
    </tbody>
</table>
