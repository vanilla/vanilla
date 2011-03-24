<?php if (!defined('APPLICATION')) exit();
PagerModule::Write(array('Sender' => $this, 'Limit' => 10));
?>
<table id="Log" class="AltColumns">
   <thead>
      <tr>
         <th class="CheckboxCell"><input id="SelectAll" type="checkbox" /></th>
         <th class="Alt UsernameCell"><?php echo T('Operation By', 'By'); ?></th>
         <th><?php echo T('Record Content', 'Content') ?></th>
         <th class="DateCell"><?php echo T('Applied On', 'Date'); ?></th>
      </tr>
   </thead>
   <tbody>
      <?php
      foreach ($this->Data('Log') as $Row):
      ?>
      <tr id="<?php echo "LogID_{$Row['LogID']}"; ?>">
         <td class="CheckboxCell"><input type="checkbox" name="LogID[]" value="<?php echo $Row['LogID']; ?>" /></td>
         <td class="UsernameCell"><?php echo htmlspecialchars($Row['InsertName']); ?></td>
         <td>
            <?php
               echo '<span class="Expander">', $this->FormatContent($Row), '</span>';

               echo '<div class="Tags">';
               echo '<span class="Tag '.$Row['Operation'].'Tag">'.T($Row['Operation']).'</span> ';
               echo '<span class="Tag '.$Row['RecordType'].'Tag">'.T($Row['RecordType']).'</span> ';
               echo '</div>';
            ?>
         </td>
         <td class="DateCell"><?php echo Gdn_Format::Date($Row['DateInserted']); ?></td>
      </tr>
      <?php
      endforeach;
      ?>
   </tbody>
</table>
<?php
PagerModule::Write();