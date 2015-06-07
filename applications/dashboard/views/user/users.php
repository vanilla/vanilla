<?php if (!defined('APPLICATION')) exit();
$Alt = FALSE;
$Session = Gdn::session();
$EditUser = $Session->checkPermission('Garden.Users.Edit');
$DeleteUser = $Session->checkPermission('Garden.Users.Delete');
$ViewPersonalInfo = $Session->checkPermission('Garden.PersonalInfo.View');
foreach ($this->UserData->result() as $User) {
    $Alt = $Alt ? FALSE : TRUE;
    ?>
    <tr id="<?php echo "UserID_{$User->UserID}"; ?>"<?php echo $Alt ? ' class="Alt"' : ''; ?>
        data-userid="<?php echo $User->UserID ?>">
        <!--      <td class="CheckboxCell"><input type="checkbox" name="LogID[]" value="<?php echo $User->UserID; ?>" /></td>-->
        <td><strong><?php
                echo userAnchor($User, 'Username');
                ?></strong></td>
        <?php if ($ViewPersonalInfo) : ?>
            <td class="Alt"><?php echo Gdn_Format::Email($User->Email); ?></td>
        <?php endif; ?>
        <td style="max-width: 200px;">
            <?php
            $Roles = val('Roles', $User, array());
            $RolesString = '';

            if ($User->Banned && !in_array('Banned', $Roles)) {
                $RolesString = t('Banned');
            }

            if ($User->Admin > 1) {
                $RolesString = ConcatSep(', ', $RolesString, t('System'));
            }

            foreach ($Roles as $RoleID => $RoleName) {
                $Query = http_build_query(array('Keywords' => $RoleName));
                $RolesString = ConcatSep(', ', $RolesString, '<a href="'.Url('/user/browse?'.$Query).'">'.htmlspecialchars($RoleName).'</a>');
            }
            echo $RolesString;
            ?>
        </td>
        <td class="Alt"><?php echo Gdn_Format::date($User->DateFirstVisit, 'html'); ?></td>
        <td><?php echo Gdn_Format::date($User->DateLastActive, 'html'); ?></td>
        <?php if ($ViewPersonalInfo) : ?>
            <td><?php echo htmlspecialchars($User->LastIPAddress); ?></td>
        <?php endif; ?>
        <?php
        $this->EventArguments['User'] = $User;
        $this->fireEvent('UserCell');
        ?>
        <?php if ($EditUser || $DeleteUser) { ?>
            <td><?php
                if ($EditUser)
                    echo anchor(t('Edit'), '/user/edit/'.$User->UserID, 'Popup SmallButton');

                if ($DeleteUser && $User->UserID != $Session->User->UserID)
                    echo anchor(t('Delete'), '/user/delete/'.$User->UserID, 'SmallButton');

                $this->EventArguments['User'] = $User;
                $this->fireEvent('UserListOptions');
                ?></td>
        <?php } ?>
    </tr>
<?php
}
