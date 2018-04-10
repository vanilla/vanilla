<?php if (!defined('APPLICATION')) exit(); ?>
<div class="toolbar">
<?php
PagerModule::write(['Sender' => $this, 'Limit' => 20, 'CurrentRecords' => count($this->data('Bans')), 'View' => 'pager-dashboard']);
?>
</div>
<div class="table-wrap">
    <table id="Log" class="table-data js-tj">
        <thead>
        <tr>
            <th class="column-lg"><?php echo t('Ban Item', 'Rule'); ?></th>
            <th><?php echo t('Ban Type', 'Type'); ?></th>
            <th class="column-sm"><?php echo '<span title="'.t('Number of affected users').'">'.t('User Count', 'Users').'</span>'; ?></th>
            <th class="column-sm"><?php echo '<span title="'.t('Number of blocked registrations').'">'.t('Blocked').'</span>'; ?></th>
            <th class="UsernameCell"><?php echo t('Added By'); ?></th>
            <th class="column-lg"><?php echo t('Notes'); ?></th>
            <th class="options"></th>
        </tr>
        </thead>
        <tbody>
        <?php
        foreach ($this->data('Bans') as $Row):
            ?>
            <tr id="<?php echo "BanID_{$Row['BanID']}"; ?>">
                <td><?php echo htmlspecialchars($Row['BanValue']); ?></td>
                <td><?php echo t($Row['BanType']); ?></td>
                <td>
                    <?php
                    echo anchor($Row['CountUsers'], '/dashboard/user/banned?Filter='.urlencode($this->_BanFilter($Row)));
                    ?>
                </td>
                <td>
                    <?php
                    echo $Row['CountBlockedRegistrations'];
                    ?>
                </td>
                <td class="UsernameCell"><?php echo htmlspecialchars($Row['InsertName']); ?></td>
                <td><?php echo htmlspecialchars($Row['Notes']); ?></td>
                <td class="options">
                    <div class="btn-group">
                    <?php
                    echo anchor(dashboardSymbol('edit'), "/dashboard/settings/bans/edit?id={$Row['BanID']}", 'js-modal btn btn-icon', ['aria-label' => t('Edit'), 'title' => t('Edit')]);
                    echo anchor(dashboardSymbol('delete'), "/dashboard/settings/bans/delete?id={$Row['BanID']}", 'js-modal-confirm btn btn-icon', ['aria-label' => t('Delete'), 'title' => t('Delete')]);
                    ?>
                    </div>
                </td>
            </tr>
        <?php
        endforeach;
        ?>
        </tbody>
    </table>
</div>
