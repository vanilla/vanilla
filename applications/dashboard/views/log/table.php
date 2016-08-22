<?php if (!defined('APPLICATION')) exit();
include $this->fetchViewLocation('helper_functions');
?>
<div class="table-wrap">
    <table id="Log" class="AltColumns">
        <thead>
        <tr>
            <th class="CheckboxCell" data-tj-ignore="true"><input id="SelectAll" type="checkbox"/></th>
            <th class="UsernameCell" data-tj-main="true"><?php echo t('Flagged By', 'Flagged By'); ?></th>
            <th class="PostedByCell"><?php echo t('Type', 'Type'); ?></th>
            <th class="DateCell"><?php echo t('Applied On', 'Date'); ?></th>
            <th class="PostTypeCell"><?php echo t('Posted By', 'Posted By'); ?></th>
            <th class="content-cell" data-tj-ignore="true"><?php echo t('Record Content', 'Content') ?></th>
        </tr>
        </thead>
        <tbody>
        <?php
        foreach ($this->data('Log') as $Row):
            $RecordLabel = valr('Data.Type', $Row);
            if (!$RecordLabel)
                $RecordLabel = $Row['RecordType'];
            $RecordLabel = Gdn_Form::LabelCode($RecordLabel);
            $user = userBuilder($Row, 'Insert');
            $user = Gdn::userModel()->getByUsername(val('Name', $user));
            $viewPersonalInfo = gdn::session()->checkPermission('Garden.PersonalInfo.View');

            ?>
            <tr id="<?php echo "LogID_{$Row['LogID']}"; ?>">
                <td class="CheckboxCell"><input type="checkbox" name="LogID[]" value="<?php echo $Row['LogID']; ?>"/>
                </td>
                <td class="UsernameCell">
                    <div class="media-sm">
                        <div class="media-sm-image-wrap">
                            <?php echo userPhoto($user); ?>
                        </div>
                        <div class="media-sm-content">
                            <div class="title">
                                <?php echo userAnchor($user, 'Username reverse-link'); ?>
                            </div>
                            <?php if ($viewPersonalInfo) : ?>
<!--                                <div class="media-sm-info user-email">--><?php //echo Gdn_Format::Email($user->Email); ?><!--</div>-->
                            <?php endif; ?>
                        </div>
                    </div>
                </td>
                <td class="PostType">
                    <?php echo t($RecordLabel); ?>
                </td>
                <td class="DateCell"><?php
                    echo Gdn_Format::date($Row['DateInserted'], 'html');
                    ?>
                </td>
                <td class="PostedByCell"><?php
                    $RecordUser = Gdn::userModel()->getID($Row['RecordUserID'], DATASET_TYPE_ARRAY);
                    if ($Row['RecordName']) { ?>
                        <div class="media-sm">
                            <div class="media-sm-content">
                                <div class="media-sm-title username"><?php echo userAnchor($Row, 'Meta-Value', 'Record'); ?>
                                    <?php
                                    if ($RecordUser['Banned']) {
                                        echo ' <span class="Tag Tag-Ban">'.t('Banned').'</span>';
                                    }
                                    echo ' <span class="Count">'.plural($RecordUser['CountDiscussions'] + $RecordUser['CountComments'], '%s post', '%s posts').'</span>';
                                    ?>
                                </div>
                                <?php if ($viewPersonalInfo && val('RecordIPAddress', $Row)) { ?>
                                    <div class="media-sm-info"><?php echo iPAnchor($Row['RecordIPAddress'], 'Meta-Value'); ?></div>
                                <?php } ?>
                            </div>
                        </div>
                    <?php } ?>
                </td>
                <td class="content-cell">
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

                    if ($Url) {
                        echo '<div class="pull-right">'.anchor(dashboardSymbol('external-link'), $Url, 'icon icon-text').'</div>';
                    }
                    echo '<div class="post-content Expander">', $this->FormatContent($Row), '</div>';

                    // Write the other record counts.

                    echo OtherRecordsMeta($Row['Data']);

                    echo '<div class="Meta-Container">';
                    if ($Row['CountGroup'] > 1) {
                        echo ' <span class="info">',
                            '<span class="Meta-Label">'.t('Reported').'</span> ',
                        wrap(Plural($Row['CountGroup'], '%s time', '%s times'), 'span', 'Meta-Value'),
                        '</span> ';
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
            </tr>
        <?php
        endforeach;
        ?>
        </tbody>
    </table>
</div>
<?php
