<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::session();
$EditUser = $Session->checkPermission('Garden.Users.Edit');
$ViewPersonalInfo = $Session->checkPermission('Garden.PersonalInfo.View');

helpAsset(t('Heads Up!'), t('Search by user or role.', 'Search for users by name or email, optionally using % as a wildcard. You can also search by user ID, the name of a role, or &ldquo;banned&rdquo;.'));
helpAsset(t('Need More Help?'), anchor(t("Video tutorial on finding &amp; managing users"), 'settings/tutorials/users'));

if (checkPermission('Garden.Users.Add')) {
    echo heading(t('Manage Users'), t('Add User'), 'dashboard/user/add', 'js-modal btn btn-primary');
} else {
    echo heading(t('Manage Users'));
}
?>
<div class="toolbar">
    <div class="toolbar-main">
        <?php
        $info = '';
        $count = $this->data('RecordCount', $this->data('UserCount', null));
        if ($count !== null) {
            $info = sprintf(plural($count, '%s user found.', '%s users found.'), $count);
        } elseif ($this->data('UserEstimate', null) !== null) {
            $info = sprintf(t('Approximately %s users exist.'), $this->data('UserEstimate'));
        }
        echo $this->Form->searchForm('Keywords', '/user/browse', [], $info);
        ?>
    </div>
    <?php PagerModule::write(['Sender' => $this, 'View' => 'pager-dashboard']); ?>
</div>
    <div class="table-wrap">
        <table id="Users" class="table-data js-tj">
            <thead>
            <tr>
                <!--         <th class="CheckboxCell"><input id="SelectAll" type="checkbox" /></th>-->
                <th class="column-lg"><?php echo anchor(t('Username'), $this->_OrderUrl('Name')); ?></th>
                <th><?php echo t('Roles'); ?></th>
                <th class="column-md"><?php echo anchor(t('First Visit'), $this->_OrderUrl('DateFirstVisit')); ?></th>
                <th class="column-md"><?php echo anchor(t('Last Visit'), $this->_OrderUrl('DateLastActive')); ?></th>
                <?php if ($ViewPersonalInfo) : ?>
                    <th><?php echo t('Last IP'); ?></th>
                <?php endif; ?>
                <?php
                $this->fireEvent('UserCell');
                ?>
                <?php if ($EditUser) { ?>
                    <th class="options column-md"></th>
                <?php } ?>
            </tr>
            </thead>
            <tbody>
            <?php include($this->fetchViewLocation('users')); ?>
            </tbody>
        </table>
    </div>
<?php
