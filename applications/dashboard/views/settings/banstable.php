<?php if (!defined('APPLICATION')) exit(); ?>
<div class="toolbar">
<?php
PagerModule::write(array('Sender' => $this, 'Limit' => 20, 'CurrentRecords' => count($this->data('Bans')), 'View' => 'pager-dashboard'));
?>
</div>
<div class="table-wrap">
    <table id="Log" class="AltColumns">
        <thead>
        <tr>
            <th><?php echo t('Ban Item', 'Item'); ?></th>
            <th><?php echo t('Ban Type', 'Type'); ?></th>
            <th class="CenterCell"><?php echo t('User Count', 'Users'); ?></th>
            <th class="CenterCell"><?php echo '<span title="'.t('Number of blocked registrations').'">', t('Blocked'), '</span>'; ?></th>
            <th class="UsernameCell"><?php echo t('Added By'); ?></th>
            <th><?php echo t('Notes'); ?></th>
            <th class="options"><?php echo t('Options'); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php
        foreach ($this->data('Bans') as $Row):
            ?>
            <tr id="<?php echo "BanID_{$Row['BanID']}"; ?>">
                <td><?php echo htmlspecialchars($Row['BanValue']); ?></td>
                <td><?php echo t($Row['BanType']); ?></td>
                <td class="CenterCell">
                    <?php
                    echo anchor($Row['CountUsers'], '/dashboard/user?Filter='.urlencode($this->_BanFilter($Row)));
                    ?>
                </td>
                <td class="CenterCell">
                    <?php
                    echo $Row['CountBlockedRegistrations'];
                    ?>
                </td>
                <td class="UsernameCell"><?php echo htmlspecialchars($Row['InsertName']); ?></td>
                <td><?php echo htmlspecialchars($Row['Notes']); ?></td>
                <td>
                    <div class="btn-group">
                    <?php
                    echo anchor(dashboardSymbol('edit'), "/dashboard/settings/bans/edit?id={$Row['BanID']}", 'js-modal btn btn-icon', ['aria-label' => t('Edit')]);
                    echo anchor(dashboardSymbol('delete'), "/dashboard/settings/bans/delete?id={$Row['BanID']}", 'js-modal-confirm btn btn-icon', ['aria-label' => t('Delete'), 'data-httpMethod' => 'post']);
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
