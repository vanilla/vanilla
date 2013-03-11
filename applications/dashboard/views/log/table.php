<?php if (!defined('APPLICATION')) exit();
include $this->FetchViewLocation('helper_functions');

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
         $RecordLabel = GetValueR('Data.Type', $Row);
         if (!$RecordLabel)
            $RecordLabel = $Row['RecordType'];
         $RecordLabel = Gdn_Form::LabelCode($RecordLabel);

      ?>
      <tr id="<?php echo "LogID_{$Row['LogID']}"; ?>">
         <td class="CheckboxCell"><input type="checkbox" name="LogID[]" value="<?php echo $Row['LogID']; ?>" /></td>
         <td class="UsernameCell"><?php 
            echo UserAnchor($Row, '', 'Insert');

            if (!empty($Row['OtherUserIDs'])) {
               $OtherUserIDs = explode(',',$Row['OtherUserIDs']);
               echo ' '.Plural(count($OtherUserIDs), 'and %s other', 'and %s others').' ';
            };
         ?></td>
         <td>
            <?php
               $Url = FALSE;
               if (in_array($Row['Operation'], array('Edit', 'Moderate'))) {
                  switch (strtolower($Row['RecordType'])) {
                     case 'discussion':
                        $Url = "/discussion/{$Row['RecordID']}/x/p1";
                        break;
                     case 'comment':
                        $Url = "/discussion/comment/{$Row['RecordID']}#Comment_{$Row['RecordID']}";
                  }
               }

               echo '<div"><span class="Expander">', $this->FormatContent($Row), '</span></div>';
               
               // Write the other record counts.
               
               echo OtherRecordsMeta($Row['Data']);

               echo '<div class="Meta-Container">';

               echo '<span class="Tags">';
               echo '<span class="Tag Tag-'.$Row['Operation'].'">'.T($Row['Operation']).'</span> ';
               echo '<span class="Tag Tag-'.$RecordLabel.'">'.Anchor(T($RecordLabel), $Url).'</span> ';
               
               echo '</span>';

               if ($Row['RecordIPAddress']) {
                  echo ' <span class="Meta">',
                     '<span class="Meta-Label">IP</span> ',
                     IPAnchor($Row['RecordIPAddress'], 'Meta-Value'),
                     '</span> ';
               }

               if ($Row['CountGroup'] > 1) {
                  echo ' <span class="Meta">',
                  '<span class="Meta-Label">'.T('Reported').'</span> ',
                  Wrap(Plural($Row['CountGroup'], '%s time', '%s times'), 'span', 'Meta-Value'),
                  '</span> ';

//                  echo ' ', sprintf(T('%s times'), $Row['CountGroup']);
               }
               
               $RecordUser = Gdn::UserModel()->GetID($Row['RecordUserID'], DATASET_TYPE_ARRAY);

               if ($Row['RecordName']) {
                  echo ' <span class="Meta">',
                     '<span class="Meta-Label">'.sprintf(T('%s by'), T($RecordLabel)).'</span> ',
                     UserAnchor($Row, 'Meta-Value', 'Record');
                  
                  if ($RecordUser['Banned']) {
                     echo ' <span class="Tag Tag-Ban">'.T('Banned').'</span>';
                  }
                  
                  echo ' <span class="Count">'.Plural($RecordUser['CountDiscussions'] + $RecordUser['CountComments'], '%s post', '%s posts').'</span>';
                  
                  
                  echo '</span> ';
               }

               // Write custom meta information.
               $CustomMeta = GetValueR('Data._Meta', $Row, FALSE);
               if (is_array($CustomMeta)) {
                  foreach ($CustomMeta as $Key => $Value) {
                     echo ' <span class="Meta">',
                        '<span class="Meta-Label">'.T($Key).'</span> ',
                        Wrap(Gdn_Format::Html($Value), 'span', array('class' => 'Meta-Value')),
                        '</span>';

                  }
               }
              
               echo '</div>';
            ?>
            
         </td>
         <td class="DateCell"><?php
            echo Gdn_Format::Date($Row['DateInserted'], 'html');
         ?></td>
      </tr>
      <?php
      endforeach;
      ?>
   </tbody>
</table>
<?php
PagerModule::Write();