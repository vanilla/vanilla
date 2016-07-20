<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::session();
?>
    <?php Gdn_Theme::assetBegin('Help'); ?>
    <div class="Help Aside">
        <?php echo '<h2>'.sprintf(t('About %s'), t('Messages')).'</h2>';
        echo t('Messages can appear anywhere in your application.', 'Messages can appear anywhere in your application, and can be used to inform your users of news and events. Use this page to re-organize your messages by dragging them up or down.');
        echo '<h2>', t('Need More Help?'), '</h2>';
        echo '<ul>';
        echo wrap(Anchor(t("Video tutorial on managing appearance"), 'settings/tutorials/appearance'), 'li');
        echo '</ul>';
        ?>
    </div>
    <?php Gdn_Theme::assetEnd(); ?>
    <div class="header-block">
        <h1><?php echo t('Manage Messages'); ?></h1>
        <?php echo anchor(t('Add Message'), 'dashboard/message/add', 'js-modal btn btn-primary'); ?>
    </div>
<?php if ($this->MessageData->numRows() > 0) { ?>
<div class="table-wrap">
    <table id="MessageTable" border="0" cellpadding="0" cellspacing="0" class="AltColumns Sortable no-hover">
        <thead>
        <tr id="0">
            <th><?php echo t('Messages'); ?></th>
            <th><?php echo t('Type'); ?></th>
            <th class="options"></th>
        </tr>
        </thead>
        <tbody>
        <?php
        foreach ($this->MessageData->result() as $Message) {
            $Message = $this->MessageModel->DefineLocation($Message);
            ?>
            <tr id="<?php
            echo $Message->MessageID;
            ?>">
                <td><?php
                    printf(
                        t('%1$s on %2$s'),
                        val($Message->AssetTarget, $this->_GetAssetData(), 'Custom Location'),
                        val($Message->Location, $this->_GetLocationData(), 'Custom Page')
                    );

                    if (val('CategoryID', $Message) && $Category = CategoryModel::categories($Message->CategoryID)) {
                        echo '<div>'.
                            anchor($Category['Name'], CategoryUrl($Category));

                        if (val('IncludeSubcategories', $Message)) {
                            echo ' '.t('and subcategories');
                        }

                        echo '</div>';
                    } else {
                        echo '<div>'.t('All Categories').'</div>';
                    }
                    if (val('AllowDismiss', $Message) == '1') {
                        echo '<div>'.t('Dismissable').'</div>';
                    } else {
                        echo '<div>'.t('Not Dismissable').'</div>';
                    }
                    ?>
                </td>
                <td class="message-type">
                    <?php
                    $cssClass = val('CssClass', $Message);
                    switch ($cssClass) {
                        case 'CasualMessage':
                            $type =  t('Casual');
                            break;
                        case 'InfoMessage':
                            $type = t('Information');
                            break;
                        case 'AlertMessage':
                            $type = t('Alert');
                            break;
                        case 'WarningMessage':
                            $type = t('Warning');
                            break;
                        default:
                            $type = t('Casual');
                    }
                    echo $type;
                    ?>
                </td>
                <td class="options">
                    <div class="btn-group">
                        <?php
                        echo anchor(dashboardSymbol('edit'), '/dashboard/message/edit/'.$Message->MessageID, 'js-modal btn btn-icon', ['aria-label' => t('Edit')]);
                        echo anchor(dashboardSymbol('delete'), '/dashboard/message/delete/'.$Message->MessageID.'/'.$Session->TransientKey(), 'js-modal-confirm btn btn-icon', ['aria-label' => t('Delete')]);
                        ?>
                    </div>
                    <span id="toggle-<?php echo $messageID = val('MessageID', $Message); ?>">
                        <?php
                        if ($Message->Enabled == '1') {
                            echo wrap(anchor('<div class="toggle-well"></div><div class="toggle-slider"></div>', '/dashboard/message/disable/'.$messageID, 'Hijack'), 'span', array('class' => "toggle-wrap toggle-wrap-on ActivateSlider ActivateSlider-Active"));
                        } else {
                            echo wrap(anchor('<div class="toggle-well"></div><div class="toggle-slider"></div>', '/dashboard/message/enable/'.$messageID, 'Hijack'), 'span', array('class' => "toggle-wrap toggle-wrap-off ActivateSlider ActivateSlider-Inactive"));
                        }
                        ?>
                    </span>
                </td>
            </tr>
            <tr class="attach-top">
                <td colspan="3">
                    <div class="Message DismissMessage <?php echo $Message->CssClass; ?>">
                        <?php echo Gdn_Format::text($Message->Content); ?>
                    </div>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>
<?php } ?>
