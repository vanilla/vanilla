<?php if (!defined('APPLICATION')) exit();
helpAsset(sprintf(t('About %s'), t('Pockets')), t('Pockets allow you to add free-form HTML to various places around the application.'));
echo heading($this->data('Title'), sprintf(t('Add %s'), t('Pocket')), 'settings/pockets/add', 'btn btn-primary js-modal');
?>
<div class="form-group">
    <div class="label-wrap-wide">
        <div class="label"><?php echo t('Enable Pocket Locations'); ?></div>
        <div class="info">
            <?php echo t('This option shows/hides the locations where pockets can go.', 'This option shows/hides the locations where pockets can go, but only for users that have permission to add/edit pockets. Try enabling this setting and then visit your site.'); ?>
        </div>
    </div>
    <div class="input-wrap-right">
        <span id="pocket-locations-toggle">
            <?php
            echo PocketsPlugin::locationsToggle(c('Plugins.Pockets.ShowLocations'));
            ?>
        </span>
    </div>
</div>
<div class="table-wrap">
    <table id="Pockets" class="table-data js-tj">
        <thead>
            <tr>
                <th class="column-md"><?php echo t('Name'); ?></th>
                <th class="column-xl"><?php echo t('Pocket'); ?></th>
                <th class="column-md"></th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach ($this->data('PocketData') as $PocketRow) {
                echo '<tr'.($PocketRow['Disabled'] != Pocket::DISABLED ? '' : ' class="Disabled"').'>';

                echo '<td>',
                    '<strong>', htmlspecialchars($PocketRow['Name']), '</strong>';

                $meta = $PocketRow['Meta'];
                echo '<div class="table-meta">';
                foreach ($meta as $metaItem) {
                    $label = wrap(sprintf(t('%s: %s'), val('label', $metaItem), ''), 'span', ['class' => 'table-meta-item-label']);
                    $value = wrap(val('value', $metaItem), 'span', ['class' => 'table-meta-item-data']);
                    echo '<div class="table-meta-item">'.$label.$value.'</div>';
                }

                echo '</div>';
                echo '</td>';
                echo '<td><pre style="white-space: pre-wrap;">', nl2br(htmlspecialchars(substr($PocketRow['Body'], 0, 200))), '</pre></td>';
                echo '<td class="options"><div class="btn-group">';
                echo anchor(dashboardSymbol('edit'), "/settings/pockets/edit/{$PocketRow['PocketID']}", 'js-modal btn btn-icon', ['aria-label' => t('Edit'), 'title' => t('Edit'), 'data-content' => ['cssClass' => 'pockets']]);
                echo anchor(dashboardSymbol('delete'), "/settings/pockets/delete/{$PocketRow['PocketID']}", 'Popup btn btn-icon', ['aria-label' => t('Delete'), 'title' => t('Delete')]);
                echo renderPocketToggle($PocketRow);
                echo '</div></td>';
                echo "</tr>\n";
            }
            ?>
        </tbody>
    </table>
</div>
