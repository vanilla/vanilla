<?php if (!defined("APPLICATION")) {
    exit();
}

$Alt = false;
$Session = Gdn::session();
$EditUser = $Session->checkPermission("Garden.Users.Edit");
$DeleteUser = $Session->checkPermission("Garden.Users.Delete");
$ViewPersonalInfo = $Session->checkPermission("Garden.PersonalInfo.View");

//some data to send to front end as props
$ranksData = [];
$profileFields = [];
$passwordLength = Gdn::config("Garden.Password.MinLength");

if (Gdn::config("Feature.CustomProfileFields.Enabled")) {
    $ranksData = Gdn::eventManager()->fireFilter("user_add_edit_form", [], $this);
    $profileFields = $this->getProfileFields();
}
foreach ($this->UserData->result() as $User) {

    $Alt = !$Alt;
    $userBlock = new MediaItemModule(val("Name", $User), userUrl($User));
    $userBlock
        ->setView("media-sm")
        ->setImage(userPhotoUrl($User))
        ->addMetaIf($ViewPersonalInfo, Gdn_Format::email($User->Email));
    ?>
    <tr id="<?php echo "UserID_{$User->UserID}"; ?>"<?php echo $Alt ? ' class="Alt"' : ""; ?>
        data-userid="<?php echo $User->UserID; ?>">
        <!--      <td class="CheckboxCell"><input type="checkbox" name="LogID[]" value="<?php echo $User->UserID; ?>" /></td>-->
        <td>
            <?php echo $userBlock; ?>
        </td>
        <td style="max-width: 200px;">
            <?php
            $Roles = val("Roles", $User, []);
            $RolesString = "";

            if ($User->Banned && !in_array("Banned", $Roles)) {
                $RolesString = t("Banned");
            }

            if ($User->Admin > 1) {
                $RolesString = concatSep(", ", $RolesString, t("System"));
            }

            foreach ($Roles as $RoleID => $RoleName) {
                $Query = http_build_query(["Keywords" => $RoleName]);
                $RolesString = concatSep(
                    ", ",
                    $RolesString,
                    '<a href="' . url("/user/browse?" . $Query) . '">' . htmlspecialchars($RoleName) . "</a>"
                );
            }
            echo $RolesString;
            ?>
        </td>
        <td class="Alt"><?php echo Gdn_Format::date($User->DateFirstVisit, "html"); ?></td>
        <td><?php echo Gdn_Format::date($User->DateLastActive, "html"); ?></td>
        <?php if ($ViewPersonalInfo): ?>
            <td><?php echo formatIP($User->LastIPAddress); ?></td>
        <?php endif; ?>
        <?php
        $this->EventArguments["User"] = $User;
        $this->fireEvent("UserCell");
        ?>
        <?php if ($EditUser || $DeleteUser) { ?>
            <td class="options">
                <div class="btn-group">
                <?php
                if ($EditUser) {
                    if (!Gdn::config("Feature.CustomProfileFields.Enabled")) {
                        echo anchor(dashboardSymbol("edit"), "/user/edit/" . $User->UserID, "js-modal btn btn-icon", [
                            "aria-label" => t("Edit"),
                            "title" => t("Edit"),
                        ]);
                    } else {
                        echo \Vanilla\Web\TwigStaticRenderer::renderReactModule("DashboardAddEditUser", [
                            "userData" => [
                                "userID" => $User->UserID,
                                "name" => $User->Name,
                                "email" => $User->Email,
                                "showEmail" => $User->ShowEmail,
                                "bypassSpam" => $User->Verified,
                                "emailConfirmed" => $User->Confirmed,
                                "banned" => $User->Banned,
                                "rankID" => $User->RankID,
                                "roles" => $User->Roles,
                                "private" => $User->Attributes["Private"] ?? false,
                                "password" => $User->Password,
                            ],
                            "ranks" => $ranksData["ranks"],
                            "profileFields" => $profileFields,
                            "passwordLength" => $passwordLength,
                        ]);
                    }
                }
                if ($DeleteUser && $User->UserID != $Session->User->UserID) {
                    echo anchor(dashboardSymbol("delete"), "/user/delete/" . $User->UserID, "btn btn-icon", [
                        "aria-label" => t("Delete"),
                        "title" => t("Delete"),
                    ]);
                }
                $this->EventArguments["User"] = $User;
                $this->fireEvent("UserListOptions");
                ?>
                </div>
            </td>
        <?php } ?>
    </tr>
<?php
}
