<?php if (!defined('APPLICATION')) exit();
$Alt = false;
$Session = Gdn::session();
$EditUser = $Session->checkPermission('Garden.Users.Edit');
$DeleteUser = $Session->checkPermission('Garden.Users.Delete');
$ViewPersonalInfo = $Session->checkPermission('Garden.PersonalInfo.View');
foreach ($this->UserData->result() as $User) {
    $Alt = !$Alt;
    ?>
    <tr id="<?php echo "UserID_{$User->UserID}"; ?>"<?php echo $Alt ? ' class="Alt"' : ''; ?>
        data-userid="<?php echo $User->UserID ?>">
        <!--      <td class="CheckboxCell"><input type="checkbox" name="LogID[]" value="<?php echo $User->UserID; ?>" /></td>-->
        <td>
            <div class="media-sm">
                <div class="media-sm-image-wrap">
                    <?php echo userPhoto($User); ?>
                </div>
                <div class="media-sm-content">
                    <div class="media-sm-title username">
                        <?php echo userAnchor($User, 'Username'); ?>
                    </div>
                    <?php if ($ViewPersonalInfo) : ?>
                    <div class="media-sm-info user-email"><?php echo Gdn_Format::Email($User->Email); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </td>
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
            <td><?php echo formatIP($User->LastIPAddress); ?></td>
        <?php endif; ?>
        <?php
        $this->EventArguments['User'] = $User;
        $this->fireEvent('UserCell');
        ?>
        <?php if ($EditUser || $DeleteUser) { ?>
            <td>
                <div class="btn-group">
                <?php
                if ($EditUser) {
                    echo anchor(dashboardSymbol('edit'), '/user/edit/'.$User->UserID, 'js-modal btn btn-icon', ['aria-label' => t('Edit')]);
                }
                if ($DeleteUser && $User->UserID != $Session->User->UserID) {
                    echo anchor(dashboardSymbol('delete'), '/user/delete/'.$User->UserID, 'btn btn-icon', ['aria-label' => t('Delete')]);
                }
                $this->EventArguments['User'] = $User;
                $this->fireEvent('UserListOptions');
                ?>
                </div>
            </td>
        <?php } ?>
    </tr>
<?php
}
