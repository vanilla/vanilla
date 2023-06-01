<?php if (!defined("APPLICATION")) {
    exit();
} ?>

<?php
/* @var RoleController $this */
echo heading($this->title(), "", "", [], "/dashboard/role");
echo $this->Form->open();
echo $this->Form->errors();
?>
<div id="defaultType" class="alert alert-info padded"><?php echo icon("exclamation-sign") .
    t("The Default Type setting has been replaced by the Automate Role Assignment Setting.") .
    "  " .
    anchor(t("Learn More."), "https://success.vanillaforums.com/kb/articles/90", ["target" => "_blank"]) .
    icon("remove"); ?></div>
<ul role="presentation">
    <li class="form-group row" role="presentation">
        <div class="label-wrap">
            <?php echo $this->Form->label("Role Name", "Name"); ?>
        </div>
        <div class="input-wrap">
            <?php echo $this->Form->textBox("Name"); ?>
        </div>
    </li>
    <li class="form-group row" role="presentation">
        <div class="label-wrap">
            <?php echo $this->Form->label("Description", "Description"); ?>
        </div>
        <div class="input-wrap">
            <?php echo $this->Form->textBox("Description", ["MultiLine" => true]); ?>
        </div>
    </li>
    <?php if ($this->data("_roleSyncVisible")) { ?>
        <li class="form-group row" role="presentation">
            <div class="label-wrap-wide">
                <?php
                echo $this->Form->label("SSO Role", "Sync");
                echo '<div class="info">' . t("SSO roles are always passed through SSO.") . "</div>";
                ?>
            </div>
            <div class="input-wrap-right">
                <?php echo $this->Form->toggle("Sync", "", ["Value" => "sso"]); ?>
            </div>
        </li>
    <?php } ?>
    <li class="form-group row" role="presentation">
        <div class="label-wrap-wide">
            <?php
            echo $this->Form->label("Personal Info", "PersonalInfo");
            echo '<div class="info">' . t("RolePersonalInfo") . "</div>";
            ?>
        </div>
        <div class="input-wrap-right">
            <?php echo $this->Form->toggle("PersonalInfo", "", ["value" => "1"]); ?>
        </div>
    </li>
    <li class="form-group row" role="presentation">
        <div class="label-wrap-wide">
            <?php
            $name = $this->Form->getValue("EnableType")
                ? "Enable automatic role assignment"
                : "Automate role assignment";
            echo $this->Form->label($name, "Type", [
                "class" => "roleType",
                "on" => t("Enable automatic role assignment"),
                "off" => t("Automate role assignment"),
            ]);
            ?>
        </div>
        <div class="input-wrap-right">
            <?php echo $this->Form->toggle("EnableType", "", ["value" => "1"]); ?>
        </div>
    </li>
    <?php if (!in_array($this->Role->RoleID, RoleModel::DEFAULT_ROLE_IDS)) { ?>
        <li class="form-group row AutomationType" role="presentation">
            <div class="label-wrap">
                <?php echo $this->Form->label("Automatically assign this role to:", "Type"); ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->dropDown("Type", $this->data("_Types"), ["IncludeNull" => true]); ?>
            </div>
        </li>
    <?php } ?>
    <li class="form-group AutomationType"  role="presentation">
        <div class="label-wrap">
            <?php
            echo $this->Form->label("Domains", "Domains");
            echo '<div class="info">' .
                t(
                    "RoleDomainInfo",
                    "Assign new confirmed users to this role if their email is from one of these domains (space-separated). The user role will only be changed once they have confirmed their email address."
                ) .
                "</div>";
            ?>
        </div>
        <?php echo $this->Form->textBoxWrap("Domains", ["MultiLine" => true]); ?>
    </li>
    <?php
    $this->fireEvent("BeforeRolePermissions");
    echo $this->Form->simple($this->data("_ExtendedFields", []), ["Wrap" => ["", ""]]);
    ?>
</ul>

<?php if (count($this->PermissionData) > 0) { ?>
    <section>
        <?php
        echo subheading(t("Check all permissions that apply to this role:"));
        if ($this->Role && $this->Role->CanSession != "1") { ?>
            <div class="alert alert-warning padded"><?php echo t(
                'Heads Up! This is a special role that does not allow active sessions. For this reason, the permission options have been limited to "view" permissions.'
            ); ?></div>
        <?php }
        ?>
        <div class="RolePermissions">
            <?php echo $this->Form->checkBoxGridGroups($this->PermissionData, "Permission"); ?>
        </div>
    </section>
<?php } ?>
<?php echo $this->Form->close("Save"); ?>
