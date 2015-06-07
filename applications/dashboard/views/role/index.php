<?php if (!defined('APPLICATION')) exit();
$Advanced = TRUE;
?>
    <div class="Help Aside">
        <?php
        echo wrap(t('Need More Help?'), 'h2');
        echo '<ul>';
        echo wrap(Anchor(t("Video tutorial on managing roles &amp; permissions"), 'settings/tutorials/roles-and-permissions'), 'li');
        echo wrap(Anchor('Default Role Types', 'http://docs.vanillaforums.com/features/roles-permissions/default-role-types/'), 'li');
        echo '</ul>';
        ?>
    </div>
    <h1><?php echo t('Manage Roles & Permissions'); ?></h1>
<?php
echo $this->Form->open();
$this->DefaultRolesWarning();
?>
    <div class="Info"><?php
        echo t('Roles determine user\'s permissions.', 'Every user in your site is assigned to at least one role. Roles are used to determine what the users are allowed to do.');
        $this->fireEvent('AfterRolesInfo');
        ?></div>
<?php if ($Advanced) { ?>
    <div class="FilterMenu"><?php echo anchor(t('Add Role'), 'dashboard/role/add', 'SmallButton'); ?></div>
<?php } ?>
    <table border="0" cellpadding="0" cellspacing="0" class="AltColumns Sortable" id="RoleTable">
        <thead>
        <tr id="0">
            <th><?php echo t('Role'); ?></th>
            <th class="Alt"><?php echo t('Description'); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php
        $Alt = FALSE;
        foreach ($this->data('Roles') as $Role) {
            $Alt = $Alt ? FALSE : TRUE;
            ?>
            <tr id="<?php echo $Role['RoleID']; ?>"<?php echo $Alt ? ' class="Alt"' : ''; ?>>
                <td class="Info">
                    <strong><?php echo $Role['Name']; ?></strong>
                    <?php if ($Advanced && $Role['CanModify']) { ?>
                        <div>
                            <?php
                            echo anchor(t('Edit'), "/role/edit/{$Role['RoleID']}", 'SmallButton');
                            if ($Role['Deletable'])
                                echo anchor(t('Delete'), "/role/delete/{$Role['RoleID']}", 'Popup SmallButton');
                            ?>
                        </div>
                    <?php } ?>
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
            </tr>
        <?php } ?>
        </tbody>
    </table>
<?php
echo $this->Form->close();
