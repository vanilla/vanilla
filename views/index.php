<?php if (!defined('APPLICATION')) exit();
Gdn_Theme::assetBegin('Help');
echo '<h2>'.sprintf(t('About %s'), t('Pockets')).'</h2>';
echo '<div>'.t('Pockets allow you to add free-form HTML to various places around the application.').'</div>';
Gdn_Theme::assetEnd();
?>
<div class="header-block">
<?php echo wrap($this->data('Title'), 'h1');
echo anchor(sprintf(t('Add %s'), t('Pocket')), 'settings/pockets/add', 'btn btn-primary js-modal'); ?>
</div>
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
            if (!c('Plugins.Pockets.ShowLocations')) {
                echo wrap(anchor('<div class="toggle-well"></div><div class="toggle-slider"></div>', '/settings/pockets/showlocations'), 'span', array('class' => "toggle-wrap toggle-wrap-off"));
            } else {
                echo wrap(anchor('<div class="toggle-well"></div><div class="toggle-slider"></div>', '/settings/pockets/hidelocations'), 'span', array('class' => "toggle-wrap toggle-wrap-on"));
            }
            ?>
        </span>
    </div>
</div>
<div class="table-wrap">
    <table id="Pockets" class="table-data">
        <thead>
            <tr>
                <th class="column-md"><?php echo t('Name'); ?></th>
                <th class="column-xl"><?php echo t('Pocket'); ?></th>
                <th class="column-sm"></th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach ($this->data('PocketData') as $PocketRow) {
                 $MobileOnly = $PocketRow['MobileOnly'];
                 $MobileNever = $PocketRow['MobileNever'];
                 $NoAds = $PocketRow['Type'] == Pocket::TYPE_AD;

                echo '<tr'.($PocketRow['Disabled'] != Pocket::DISABLED ? '' : ' class="Disabled"').'>';

                echo '<td>',
                    '<strong>', htmlspecialchars($PocketRow['Name']), '</strong>';
                    if ($notes = $PocketRow['Notes']) {
                        echo '<div class="info pocket-notes">'.sprintf(t('%s: %s'), t('Notes'), $notes).'</div>';
                    }
                    if ($page = htmlspecialchars($PocketRow['Page'])) {
                        echo '<div class="info pocket-page">'.sprintf(t('%s: %s'), t('Page'), $page).'</div>';
                    }
                    if ($location = htmlspecialchars($PocketRow['Location'])) {
                        echo '<div class="info pocket-location">'.sprintf(t('%s: %s'), t('Location'), $location).'</div>';
                    }
                    if ($MobileOnly) {
                        echo '<div class="info">(', t('Shown only on mobile'), ')</div>';
                    }
                    if ($MobileNever) {
                        echo '<div class="info">(', t('Hidden for mobile'), ')</div>';
                    }
                    if ($MobileNever && $MobileOnly) {
                        echo '<div class="info">(', t('Hidden for everything!'), ')</div>';
                    }
                    if ($NoAds) {
                        echo '<div class="info">(', t('Users with the no ads permission will not see this pocket.'), ')</div>';
                    }
                    '</td>';
                echo '<td><pre style="white-space: pre-wrap;">', nl2br(htmlspecialchars(substr($PocketRow['Body'], 0, 200))), '</pre></td>';
                echo '<td class="options"><div class="btn-group">';
                echo anchor(dashboardSymbol('edit'), "/settings/pockets/edit/{$PocketRow['PocketID']}", 'js-modal btn btn-icon', ['aria-label' => t('Edit'), 'title' => t('Edit'), 'data-content' => ['cssClass' => 'pockets']]);
                echo anchor(dashboardSymbol('delete'), "/settings/pockets/delete/{$PocketRow['PocketID']}", 'Popup btn btn-icon', ['aria-label' => t('Delete'), 'title' => t('Delete')]);
                echo '</div></td>';
                echo "</tr>\n";
            }
            ?>
        </tbody>
    </table>
</div>
