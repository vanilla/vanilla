<?php if (!defined('APPLICATION')) exit();

echo Wrap($this->Data('Title'), 'h1');
?>
<div class="Info"><?php echo T('Pockets allow you to add free-form HTML to various places around the application.'); ?>

<table>
   <tr>
      <td width="200"><?php
      if (C('Plugins.Pockets.ShowLocations')) {
         echo Anchor(T('Hide Pocket Locations'), '/settings/pockets/hidelocations', 'SmallButton');
      } else {
         echo Anchor(T('Show Pocket Locations'), '/settings/pockets/showlocations', 'SmallButton');
      }
      ?></td>
      <td><?php echo T('This option shows/hides the locations where pockets can go.', 'This option shows/hides the locations where pockets can go, but only for users that have permission to add/edit pockets. Try showing the locations and then visit your site.'); ?></td>
   </tr>
   <tr>
      <td><?php echo Anchor(sprintf(T('Add %s'), T('Pocket')), 'settings/pockets/add', 'SmallButton'); ?></td>
      <td><?php echo T('Add a new Pocket to your site.'); ?></td>
   </tr>
</table></div>
<table id="Pockets" class="AltColumns">
   <thead>
      <tr>
         <th><?php echo T('Pocket'); ?></th>
         <th><?php echo T('Page'); ?></th>
         <th class="Alt"><?php echo T('Location'); ?></th>
         <th><?php echo T('Body'); ?></th>
         <th class="Alt"><?php echo T('Notes'); ?></th>
      </tr>
   </thead>
   <tbody>
      <?php
      foreach ($this->Data('PocketData') as $PocketRow) {
      	 $MobileOnly = $PocketRow['MobileOnly'];
      	 $MobileNever = $PocketRow['MobileNever'];
         echo '<tr'.($PocketRow['Disabled'] != Pocket::DISABLED ? '' : ' class="Disabled"').'>';

         echo '<td>',
            '<strong>', htmlspecialchars($PocketRow['Name']), '</strong>',
            '<div>',
            Anchor('Edit', "/settings/pockets/edit/{$PocketRow['PocketID']}"),
            ' <span>|</span> ',
            Anchor('Delete', "/settings/pockets/delete/{$PocketRow['PocketID']}", 'Popup'),
            '</div>',
            '</td>';

         echo '<td>',htmlspecialchars($PocketRow['Page']), '</td>';
         echo '<td  class="Alt">', htmlspecialchars($PocketRow['Location']); 
         if ($MobileOnly) {
         	echo '<br>(', T('Shown only on mobile'), ')';
         }
         if ($MobileNever) {
         	echo '<br>(', T('Hidden for mobile'), ')';
         }
         if ($MobileNever && $MobileOnly) {
         	echo '<br><b>(', T('Hidden for everything!'), ')</b>';
         }
         echo'</td>';
         echo '<td>', nl2br(htmlspecialchars(substr($PocketRow['Body'], 0, 200))), '</td>';
         echo '<td  class="Alt">', $PocketRow['Notes'], '</td>';

         echo "</tr>\n";
      }
      ?>
   </tbody>
</table>
<?php echo $this->Form->Close(''); ?>