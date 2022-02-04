<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::session();
$desc = t('Messages can appear anywhere in your application.', 'Messages can appear anywhere in your application, and can be used to inform your users of news and events. Use this page to re-organize your messages by dragging them up or down.');
helpAsset(sprintf(t('About %s'), t('Messages')), $desc);
helpAsset(t('Need More Help?'), anchor(t("Video tutorial on managing appearance"), 'settings/tutorials/appearance'));
echo heading(t('Manage Messages'), t('Add Message'), 'dashboard/message/add', 'js-modal btn btn-primary');
?>
<?php if ($this->MessageData->numRows() > 0) { ?>
<div class="table-wrap">
    <table id="MessageTable" border="0" cellpadding="0" cellspacing="0" class="table-data js-tj Sortable">
        <thead>
        <tr id="0">
            <th class="column-lg"><?php echo t('Messages'); ?></th>
            <th><?php echo t('Type'); ?></th>
            <th class="options column-md"></th>
        </tr>
        </thead>
        <tbody>
        <?php
        foreach ($this->MessageData->result() as $Message) {
            $Message = $this->MessageModel->defineLocation($Message);
            ?>
            <tr id="<?php
            echo $Message->MessageID;
            ?>" class="js-message-<?php echo $Message->MessageID; ?>">
                <td><?php
                    printf(
                        t('%1$s on %2$s'),
                        val($Message->AssetTarget, $this->_GetAssetData(), 'Custom Location'),
                        val($Message->Location, $this->_GetLocationData(), 'Custom Page')
                    );

                    if (val('CategoryID', $Message) && $Category = CategoryModel::categories($Message->CategoryID)) {
                        echo '<div>'.
                            anchor($Category['Name'], categoryUrl($Category));

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
                        $editAttrs = ['aria-label' => t('Edit'), 'title' => t('Edit')];
                        $deleteAttrs = ['aria-label' => t('Delete'), 'title' => t('Delete'), 'data-remove-selector' => '.js-message-'.$Message->MessageID];
                        echo anchor(dashboardSymbol('edit'), '/dashboard/message/edit/'.$Message->MessageID, 'js-modal btn btn-icon', $editAttrs);
                        echo anchor(dashboardSymbol('delete'), '/dashboard/message/delete/'.$Message->MessageID, 'js-modal-confirm btn btn-icon', $deleteAttrs);
                        ?>
                        <div id="toggle-<?php echo $messageID = val('MessageID', $Message); ?>">
                            <?php
                            if ($Message->Enabled == '1') {
                                echo wrap(anchor('<div class="toggle-well"></div><div class="toggle-slider"></div>', '/dashboard/message/disable/'.$messageID, 'Hijack'), 'span', ['class' => "toggle-wrap toggle-wrap-on"]);
                            } else {
                                echo wrap(anchor('<div class="toggle-well"></div><div class="toggle-slider"></div>', '/dashboard/message/enable/'.$messageID, 'Hijack'), 'span', ['class' => "toggle-wrap toggle-wrap-off"]);
                            }
                            ?>
                        </div>
                    </div>
                </td>
            </tr>
            <tr class="js-message-<?php echo $Message->MessageID; ?>">
                <td colspan="3"  data-tj-ignore="true">
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
