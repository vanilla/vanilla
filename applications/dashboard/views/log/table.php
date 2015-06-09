<?php if (!defined('APPLICATION')) exit();
include $this->fetchViewLocation('helper_functions');

PagerModule::write(array('Sender' => $this, 'Limit' => 10));
?>
    <table id="Log" class="AltColumns">
        <thead>
        <tr>
            <th class="CheckboxCell"><input id="SelectAll" type="checkbox"/></th>
            <th class="Alt UsernameCell"><?php echo t('Operation By', 'By'); ?></th>
            <th><?php echo t('Record Content', 'Content') ?></th>
            <th class="DateCell"><?php echo t('Applied On', 'Date'); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php
        foreach ($this->data('Log') as $Row):
            $RecordLabel = valr('Data.Type', $Row);
            if (!$RecordLabel)
                $RecordLabel = $Row['RecordType'];
            $RecordLabel = Gdn_Form::LabelCode($RecordLabel);

            ?>
            <tr id="<?php echo "LogID_{$Row['LogID']}"; ?>">
                <td class="CheckboxCell"><input type="checkbox" name="LogID[]" value="<?php echo $Row['LogID']; ?>"/>
                </td>
                <td class="UsernameCell"><?php
                    echo userAnchor($Row, '', 'Insert');

                    if (!empty($Row['OtherUserIDs'])) {
                        $OtherUserIDs = explode(',', $Row['OtherUserIDs']);
                        echo ' '.plural(count($OtherUserIDs), 'and %s other', 'and %s others').' ';
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
                    } elseif ($Row['Operation'] === 'Delete') {
                        switch (strtolower($Row['RecordType'])) {
                            case 'comment':
                                $Url = "/discussion/{$Row['ParentRecordID']}/x/p1";
                        }
                    }

                    echo '<div"><span class="Expander">', $this->FormatContent($Row), '</span></div>';

                    // Write the other record counts.

                    echo OtherRecordsMeta($Row['Data']);

                    echo '<div class="Meta-Container">';

                    echo '<span class="Tags">';
                    echo '<span class="Tag Tag-'.$Row['Operation'].'">'.t($Row['Operation']).'</span> ';
                    echo '<span class="Tag Tag-'.$RecordLabel.'">'.anchor(t($RecordLabel), $Url).'</span> ';

                    echo '</span>';

                    if (checkPermission('Garden.PersonalInfo.View') && $Row['RecordIPAddress']) {
                        echo ' <span class="Meta">',
                        '<span class="Meta-Label">IP</span> ',
                        IPAnchor($Row['RecordIPAddress'], 'Meta-Value'),
                        '</span> ';
                    }

                    if ($Row['CountGroup'] > 1) {
                        echo ' <span class="Meta">',
                            '<span class="Meta-Label">'.t('Reported').'</span> ',
                        wrap(Plural($Row['CountGroup'], '%s time', '%s times'), 'span', 'Meta-Value'),
                        '</span> ';

//                  echo ' ', sprintf(t('%s times'), $Row['CountGroup']);
                    }

                    $RecordUser = Gdn::userModel()->getID($Row['RecordUserID'], DATASET_TYPE_ARRAY);

                    if ($Row['RecordName']) {
                        echo ' <span class="Meta">',
                            '<span class="Meta-Label">'.sprintf(t('%s by'), t($RecordLabel)).'</span> ',
                        userAnchor($Row, 'Meta-Value', 'Record');

                        if ($RecordUser['Banned']) {
                            echo ' <span class="Tag Tag-Ban">'.t('Banned').'</span>';
                        }

                        echo ' <span class="Count">'.plural($RecordUser['CountDiscussions'] + $RecordUser['CountComments'], '%s post', '%s posts').'</span>';


                        echo '</span> ';
                    }

                    // Write custom meta information.
                    $CustomMeta = valr('Data._Meta', $Row, false);
                    if (is_array($CustomMeta)) {
                        foreach ($CustomMeta as $Key => $Value) {
                            echo ' <span class="Meta">',
                                '<span class="Meta-Label">'.t($Key).'</span> ',
                            wrap(Gdn_Format::Html($Value), 'span', array('class' => 'Meta-Value')),
                            '</span>';

                        }
                    }

                    echo '</div>';
                    ?>

                </td>
                <td class="DateCell"><?php
                    echo Gdn_Format::date($Row['DateInserted'], 'html');
                    ?></td>
            </tr>
        <?php
        endforeach;
        ?>
        </tbody>
    </table>
<?php
PagerModule::write();
