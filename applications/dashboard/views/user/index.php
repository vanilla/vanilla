<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::session();
$EditUser = $Session->checkPermission('Garden.Users.Edit');
$ViewPersonalInfo = $Session->checkPermission('Garden.PersonalInfo.View');
?>
    <div class="Help Aside">
        <?php
        echo wrap(t('Need More Help?'), 'h2');
        echo '<ul>';
        echo wrap(Anchor(t("Video tutorial on finding &amp; managing users"), 'settings/tutorials/users'), 'li');
        echo '</ul>';
        ?>
    </div>
    <h1><?php echo t('Manage Users'); ?></h1>
<?php echo $this->Form->open(array('action' => url('/user/browse'))); ?>
    <div class="Wrap">
        <?php
        echo $this->Form->errors();

        echo '<div>', t('Search by user or role.', 'Search for users by name or enter the name of a role to see all users with that role.'), '</div>';

        echo '<div>';
        echo $this->Form->textBox('Keywords');
        echo ' ', $this->Form->button(t('Go'));
        if ($this->data('RecordCount', null) !== null) {
            echo ' ', sprintf(t('%s user(s) found.'), $this->data('RecordCount'));
        }
        echo '</div>';

        ?>
    </div>
    <div class="Wrap">
        <!--   <span class="ButtonList">
      <?php
        echo anchor(t('Ban'), '#', 'Popup SmallButton');
        echo anchor(t('Unban'), '#', 'Popup SmallButton');
        echo anchor(t('Delete'), '#', 'Popup SmallButton');
        ?>
   </span>-->

        <?php
        if (checkPermission('Garden.Users.Add')) {
            echo anchor(t('Add User'), 'dashboard/user/add', 'Popup SmallButton');
        }
        ?>
    </div>
    <table id="Users" class="AltColumns">
        <thead>
        <tr>
            <!--         <th class="CheckboxCell"><input id="SelectAll" type="checkbox" /></th>-->
            <th><?php echo anchor(t('Username'), $this->_OrderUrl('Name')); ?></th>
            <?php if ($ViewPersonalInfo) : ?>
                <th class="Alt"><?php echo t('Email'); ?></th>
            <?php endif; ?>
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
                <th><?php echo t('Options'); ?></th>
            <?php } ?>
        </tr>
        </thead>
        <tbody>
        <?php
        include($this->fetchViewLocation('users'));
        ?>
        </tbody>
    </table>
<?php
PagerModule::write(array('Sender' => $this));
echo $this->Form->close();
