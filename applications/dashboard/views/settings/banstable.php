<?php if (!defined('APPLICATION')) exit();
PagerModule::write(array('Sender' => $this, 'Limit' => 20, 'CurrentRecords' => count($this->data('Bans'))));
?>
    <table id="Log" class="AltColumns">
        <thead>
        <tr>
            <th><?php echo t('Ban Item', 'Item'); ?></th>
            <th><?php echo t('Ban Type', 'Type'); ?></th>
            <th class="CenterCell"><?php echo t('User Count', 'Users'); ?></th>
            <th class="CenterCell"><?php echo '<span title="'.t('Number of blocked registrations').'">', t('Blocked'), '</span>'; ?></th>
            <th class="UsernameCell"><?php echo t('Added By'); ?></th>
            <th><?php echo t('Notes'); ?></th>
            <th><?php echo t('Options'); ?></th>
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
                    <?php
                    echo anchor(t('Edit'), '/dashboard/settings/bans/edit?id='.$Row['BanID'], array('class' => 'SmallButton Edit'));
                    echo ' ';
                    echo anchor(t('Delete'), '/dashboard/settings/bans/delete?id='.$Row['BanID'], array('class' => 'SmallButton Delete'));
                    ?>
                </td>
            </tr>
        <?php
        endforeach;
        ?>
        </tbody>
    </table>
<?php
PagerModule::write();
