<?php if (!defined('APPLICATION')) exit();

echo wrap($this->data('Title'), 'h1');
?>
<div class="Info"><?php echo t('Pockets allow you to add free-form HTML to various places around the application.'); ?>

<table>
    <tr>
        <td width="200"><?php
        if (C('Plugins.Pockets.ShowLocations')) {
            echo anchor(t('Hide Pocket Locations'), '/settings/pockets/hidelocations', 'SmallButton');
        } else {
            echo anchor(t('Show Pocket Locations'), '/settings/pockets/showlocations', 'SmallButton');
        }
        ?></td>
        <td><?php echo t('This option shows/hides the locations where pockets can go.', 'This option shows/hides the locations where pockets can go, but only for users that have permission to add/edit pockets. Try showing the locations and then visit your site.'); ?></td>
    </tr>
    <tr>
        <td><?php echo anchor(sprintf(t('Add %s'), t('Pocket')), 'settings/pockets/add', 'SmallButton'); ?></td>
        <td><?php echo t('Add a new Pocket to your site.'); ?></td>
    </tr>
</table></div>
<table id="Pockets" class="AltColumns">
    <thead>
        <tr>
            <th><?php echo t('Pocket'); ?></th>
            <th><?php echo t('Page'); ?></th>
            <th class="Alt"><?php echo t('Location'); ?></th>
            <th><?php echo t('Body'); ?></th>
            <th class="Alt"><?php echo t('Notes'); ?></th>
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
                '<strong>', htmlspecialchars($PocketRow['Name']), '</strong>',
                '<div>',
                anchor('Edit', "/settings/pockets/edit/{$PocketRow['PocketID']}"),
                ' <span>|</span> ',
                anchor('Delete', "/settings/pockets/delete/{$PocketRow['PocketID']}", 'Popup'),
                '</div>',
                '</td>';

            echo '<td>',htmlspecialchars($PocketRow['Page']), '</td>';
            echo '<td  class="Alt">', htmlspecialchars($PocketRow['Location']);
            if ($MobileOnly) {
            	echo '<br>(', t('Shown only on mobile'), ')';
            }
            if ($MobileNever) {
            	echo '<br>(', t('Hidden for mobile'), ')';
            }
            if ($MobileNever && $MobileOnly) {
            	echo '<br><b>(', t('Hidden for everything!'), ')</b>';
            }
            if ($NoAds) {
                echo '<br>(', t('Users with the no ads permission will not see this pocket.'), ')';
            }
            echo'</td>';
            echo '<td>', nl2br(htmlspecialchars(substr($PocketRow['Body'], 0, 200))), '</td>';
            echo '<td  class="Alt">', $PocketRow['Notes'], '</td>';

            echo "</tr>\n";
        }
        ?>
    </tbody>
</table>
<?php echo $this->Form->close(''); ?>
