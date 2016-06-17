<?php if (!defined('APPLICATION')) exit(); ?>
<?php Gdn_Theme::assetBegin('Help'); ?>
    <div><?php
        echo wrap(sprintf(t('About %s'), t('Roles & Permissions')), 'h2');
        echo t('Roles determine user\'s permissions.', 'Every user in your site is assigned to at least one role. Roles are used to determine what the users are allowed to do.');
        ?>
    </div>
    <div class="Help Aside">
        <?php
        echo wrap(t('Need More Help?'), 'h2');
        echo '<ul>';
        echo wrap(Anchor(t("Video tutorial on managing roles &amp; permissions"), 'settings/tutorials/roles-and-permissions'), 'li');
        echo wrap(Anchor('Default Role Types', 'http://docs.vanillaforums.com/features/roles-permissions/default-role-types/'), 'li');
        echo '</ul>';
        ?>
    </div>
<?php Gdn_Theme::assetEnd(); ?>
<div class="header-block">
    <h1><?php echo t('Manage Roles & Permissions'); ?></h1>
    <div class="FilterMenu"><?php echo anchor(t('Add Role'), 'dashboard/role/add', 'btn btn-primary'); ?></div>
</div>
<?php
$this->fireEvent('AfterRolesInfo');
echo $this->Form->open();
$this->DefaultRolesWarning();
?>
<div class="table-wrap">
    <table border="0" cellpadding="0" cellspacing="0" class="AltColumns Sortable" id="RoleTable">
        <thead>
        <tr id="0">
            <th><?php echo t('Role'); ?></th>
            <th class="Alt"><?php echo t('Description'); ?></th>
            <th class="options"><?php echo t('Options'); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php
        $Alt = false;
        foreach ($this->data('Roles') as $Role) {
            $Alt = !$Alt;
            ?>
            <tr id="<?php echo $Role['RoleID']; ?>"<?php echo $Alt ? ' class="Alt"' : ''; ?>>
                <td>
                    <?php echo $Role['Name']; ?>
                </td>
                <td class="Alt">
                    <?php
                    echo $Role['Description'];

                    if (val('Type', $Role)) {
                        echo '<div class="Meta-Container"><span class="Meta-Label">'.
                            t('default type', 'default').': '.
                            t(val('Type', $Role)).
                            '</span></div>';
                    }
                    ?>
                </td>
                <td>
                    <div class="btn-group">
                    <?php
                    if ($Role['CanModify']) {
                        echo anchor(dashboardSymbol('edit'), "/role/edit/{$Role['RoleID']}", 'btn btn-icon', ['aria-label' => t('Edit')]);
                        if ($Role['Deletable']) {
                            echo anchor(dashboardSymbol('delete'), "/role/delete/{$Role['RoleID']}", 'Popup btn btn-icon', ['aria-label' => t('Delete')]);
                        }
                    }
                    ?>
                    </div>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>
<?php
echo $this->Form->close();
