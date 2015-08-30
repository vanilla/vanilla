<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::session();
?>
    <div class="Help Aside">
        <?php
        echo '<h2>', t('Need More Help?'), '</h2>';
        echo '<ul>';
        echo wrap(Anchor(t("Video tutorial on managing appearance"), 'settings/tutorials/appearance'), 'li');
        echo '</ul>';
        ?>
    </div>
    <h1><?php echo t('Manage Messages'); ?></h1>
    <div
        class="Info"><?php echo t('Messages can appear anywhere in your application.', 'Messages can appear anywhere in your application, and can be used to inform your users of news and events. Use this page to re-organize your messages by dragging them up or down.'); ?></div>
    <div
        class="FilterMenu"><?php echo anchor(t('Add Message'), 'dashboard/message/add', 'AddMessage SmallButton'); ?></div>
<?php if ($this->MessageData->numRows() > 0) { ?>
    <table id="MessageTable" border="0" cellpadding="0" cellspacing="0" class="AltColumns Sortable">
        <thead>
        <tr id="0">
            <th><?php echo t('Location'); ?></th>
            <th class="Alt"><?php echo t('Message'); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php
        $Alt = FALSE;
        foreach ($this->MessageData->result() as $Message) {
            $Message = $this->MessageModel->DefineLocation($Message);
            $Alt = $Alt ? FALSE : TRUE;
            ?>
            <tr id="<?php
            echo $Message->MessageID;
            echo $Alt ? '" class="Alt' : '';
            ?>">
                <td class="Info nowrap"><?php
                    printf(
                        t('%1$s on %2$s'),
                        arrayValue($Message->AssetTarget, $this->_GetAssetData(), 'Custom Location'),
                        arrayValue($Message->Location, $this->_GetLocationData(), 'Custom Page')
                    );

                    if (val('CategoryID', $Message) && $Category = CategoryModel::categories($Message->CategoryID)) {
                        echo '<div>'.
                            anchor($Category['Name'], CategoryUrl($Category));

                        if (val('IncludeSubcategories', $Message)) {
                            echo ' '.t('and subcategories');
                        }

                        echo '</div>';
                    }
                    ?>
                    <div>
                        <strong><?php echo $Message->Enabled == '1' ? t('Enabled') : t('Disabled'); ?></strong>
                        <?php
                        echo anchor(t('Edit'), '/dashboard/message/edit/'.$Message->MessageID, 'EditMessage SmallButton');
                        echo anchor(t('Delete'), '/dashboard/message/delete/'.$Message->MessageID.'/'.$Session->TransientKey(), 'DeleteMessage SmallButton');
                        ?>
                    </div>
                </td>
                <td class="Alt">
                    <div
                        class="Message <?php echo $Message->CssClass; ?>"><?php echo Gdn_Format::text($Message->Content); ?></div>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
<?php } ?>
