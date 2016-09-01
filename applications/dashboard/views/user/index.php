<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::session();
$EditUser = $Session->checkPermission('Garden.Users.Edit');
$ViewPersonalInfo = $Session->checkPermission('Garden.PersonalInfo.View');

Gdn_Theme::assetBegin('Help');
?>
<?php Gdn_Theme::assetBegin('Help'); ?>
    <div class="Help Aside">
        <?php
        echo wrap(t('Heads up!'), 'h2');
        echo '<div>', t('Search by user or role.', 'Search for users by name or enter the name of a role to see all users with that role.'), '</div>';
        echo wrap(t('Need More Help?'), 'h2');
        echo '<ul>';
        echo wrap(Anchor(t("Video tutorial on finding &amp; managing users"), 'settings/tutorials/users'), 'li');
        echo '</ul>';
        ?>
    </div>
<?php Gdn_Theme::assetEnd('Help'); ?>
<div class="header-block">
    <div class="header-title">
    <h1><?php echo t('Manage Users'); ?></h1>
    </div>
    <div class="header-buttons btn-group">
    <?php
    if (checkPermission('Garden.Users.Add')) {
        echo anchor(t('Add User'), 'dashboard/user/add', 'Popup btn btn-primary');
    }
    ?>
    </div>
</div>
<div class="toolbar">
    <div class="toolbar-main">
        <?php
        echo $this->Form->open(array('action' => url('/user/browse')));
        echo $this->Form->errors();
        echo '<div class="search-wrap input-wrap">';
        echo '<div class="icon-wrap icon-search-wrap">'.dashboardSymbol('search').'</div>';
        echo $this->Form->textBox('Keywords');
        echo ' ', $this->Form->button(t('Go'), ['class' => 'search-submit']);
        echo '<a class="icon-wrap icon-clear-wrap" href="'.url('/user').'">'.dashboardSymbol('close').'</a>';
        echo '<div class="info search-info">';
        $count = $this->data('RecordCount', $this->data('UserCount', null));
        if ($count !== null) {
            echo ' ', sprintf(plural($count, '%s user found.', '%s users found.'), $count);
        } elseif ($this->data('UserEstimate', null) !== null) {
            echo ' ', sprintf(t('Approximately %s users exist.'), $this->data('UserEstimate'));
        }
        echo '</div>';
        echo '</div>';
        echo $this->Form->close();
        ?>
    </div>
<!--    <div class="Wrap">-->
        <!--   <span class="ButtonList">
      <?php
        echo anchor(t('Ban'), '#', 'Popup SmallButton');
        echo anchor(t('Unban'), '#', 'Popup SmallButton');
        echo anchor(t('Delete'), '#', 'Popup SmallButton');
        ?>
   </span>-->
<!--    </div>-->
    <?php PagerModule::write(array('Sender' => $this, 'View' => 'pager-dashboard')); ?>
</div>
    <div class="table-wrap">
        <table id="Users" class="AltColumns">
            <thead>
            <tr>
                <!--         <th class="CheckboxCell"><input id="SelectAll" type="checkbox" /></th>-->
                <th><?php echo anchor(t('Username'), $this->_OrderUrl('Name')); ?></th>
                <th><?php echo t('Roles'); ?></th>
                <th class="Alt"><?php echo anchor(t('First Visit'), $this->_OrderUrl('DateFirstVisit')); ?></th>
                <th><?php echo anchor(t('Last Visit'), $this->_OrderUrl('DateLastActive')); ?></th>
                <?php if ($ViewPersonalInfo) : ?>
                    <th><?php echo t('Last IP'); ?></th>
                <?php endif; ?>
                <?php
                $this->fireEvent('UserCell');
                ?>
                <?php if ($EditUser) { ?>
                    <th class="options"><?php echo t('Options'); ?></th>
                <?php } ?>
            </tr>
            </thead>
            <tbody>
            <?php include($this->fetchViewLocation('users')); ?>
            </tbody>
        </table>
    </div>
<?php
