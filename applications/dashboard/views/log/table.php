<?php if (!defined('APPLICATION')) exit();
include $this->fetchViewLocation('helper_functions');
?>
<div class="table-wrap">
    <table id="Log" class="table-data js-tj">
        <thead>
        <tr>
            <th class="column-checkbox" data-tj-ignore="true"><input id="SelectAll" type="checkbox"/></th>
            <th class="UsernameCell column-lg"><?php echo t('Flagged By', 'Flagged By'); ?></th>
            <th class="content-cell column-lg" data-tj-main="true"><?php echo t('Record Content', 'Content') ?></th>
            <th class="TypeCell column-sm"><?php echo t('Type', 'Type'); ?></th>
            <th class="DateCell column-md"><?php echo t('Applied On', 'Date'); ?></th>
            <th class="PostTypeCell column-md"><?php echo t('Posted By', 'Posted By'); ?></th>
            <th class="options column-checkbox"></th>
        </tr>
        </thead>
        <tbody>
        <?php
        foreach ($this->data('Log') as $Row):
            $RecordLabel = valr('Data.Type', $Row);
            if (!$RecordLabel)
                $RecordLabel = $Row['RecordType'];
            $RecordLabel = Gdn_Form::labelCode($RecordLabel);
            $user = userBuilder($Row, 'Insert');
            $user = Gdn::userModel()->getByUsername(val('Name', $user));
            $viewPersonalInfo = gdn::session()->checkPermission('Garden.PersonalInfo.View');

            $userBlock = new MediaItemModule(val('Name', $user), userUrl($user));
            $userBlock->setView('media-sm')
                ->setImage(userPhotoUrl($user))
                ->addMetaIf($viewPersonalInfo, Gdn_Format::email($user->Email));

            $Url = FALSE;
            if (in_array($Row['Operation'], ['Edit', 'Moderate'])) {
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

            ?>
            <tr id="<?php echo "LogID_{$Row['LogID']}"; ?>">
                <td class="column-checkbox"><input type="checkbox" name="LogID[]" value="<?php echo $Row['LogID']; ?>"/>
                </td>
                <td class="UsernameCell">
                    <?php echo $userBlock; ?>
                </td>
                <td class="content-cell">
                    <?php
                    echo '<div class="post-content Expander">', $this->formatContent($Row), '</div>';

                    // Write the other record counts.

                    echo otherRecordsMeta($Row['Data']);

                    echo '<div class="Meta-Container">';
                    if ($Row['CountGroup'] > 1) {
                        echo ' <span class="info">',
                            '<span class="Meta-Label">'.t('Reported').'</span> ',
                        wrap(plural($Row['CountGroup'], '%s time', '%s times'), 'span', 'Meta-Value'),
                        '</span> ';
                    }

                    // Write custom meta information.
                    $CustomMeta = valr('Data._Meta', $Row, false);
                    if (is_array($CustomMeta)) {
                        foreach ($CustomMeta as $Key => $Value) {
                            echo ' <span class="Meta">',
                                '<span class="Meta-Label">'.t($Key).'</span> ',
                            wrap(Gdn_Format::html($Value), 'span', ['class' => 'Meta-Value']),
                            '</span>';
                        }
                    }
                    echo '</div>';
                    ?>
                </td>
                <td class="PostType">
                    <?php echo t($RecordLabel); ?>
                </td>
                <td class="DateCell">
                    <?php echo Gdn_Format::date($Row['DateInserted'], '%a %e %b %Y %r %Z'); ?>
                </td>
                <td class="PostedByCell"><?php
                    $RecordUser = Gdn::userModel()->getID($Row['RecordUserID'], DATASET_TYPE_ARRAY);
                    if ($Row['RecordName']) {
                        $userBlock = new MediaItemModule(val('Name', $RecordUser), userUrl($RecordUser));
                        $userBlock->setView('media-sm')
                            ->addMeta(plural($RecordUser['CountDiscussions'] + $RecordUser['CountComments'], '%s post', '%s posts'))
                            ->addMetaIf($RecordUser['Banned'] ? true : false, wrap(t('Banned'), 'span', ['class' => 'text-danger']))
                            ->addMetaIf(($viewPersonalInfo && val('RecordIPAddress', $Row)), iPAnchor($Row['RecordIPAddress']));

                        echo $userBlock;
                    } ?>
                </td>
                <td class="options">
                    <?php
                    if ($Url) {
                        $attr = ['title' => t('View Post'), 'aria-label' => t('View Post'), 'class' => 'btn btn-icon btn-icon-sm'];
                        echo anchor(dashboardSymbol('external-link', 'icon icon-text'), $Url, '', $attr);
                    }
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
