<?php if (!defined('APPLICATION')) exit(); ?>
<?php
echo heading($this->title(), '', '', [], '/dashboard/role');
echo $this->Form->open();
echo $this->Form->errors();
?>
<ul>
    <li class="form-group row">
        <div class="label-wrap">
            <?php echo $this->Form->label('Role Name', 'Name'); ?>
        </div>
        <div class="input-wrap">
            <?php echo $this->Form->textBox('Name'); ?>
        </div>
    </li>
    <li class="form-group row">
        <div class="label-wrap">
            <?php echo $this->Form->label('Description', 'Description'); ?>
        </div>
        <div class="input-wrap">
            <?php echo $this->Form->textBox('Description', ['MultiLine' => true]); ?>
        </div>
    </li>
    <li class="form-group row">
        <div class="label-wrap">
            <?php echo $this->Form->label('Default Type', 'Type');
            echo '<div class="info">'.t('Select the default type for this role, if any.').'</div>'; ?>
        </div>
        <div class="input-wrap">
            <?php echo $this->Form->dropDown('Type', $this->data('_Types'), ['IncludeNull' => true]); ?>
        </div>
    </li>
    <li class="form-group row">
        <div class="input-wrap no-label">
            <?php echo $this->Form->checkBox('PersonalInfo', t('RolePersonalInfo', "This role is personal info. Only users with permission to view personal info will see it."), ['value' => '1']); ?>
        </div>
    </li>
    <?php
    $this->fireEvent('BeforeRolePermissions');

    echo $this->Form->simple(
        $this->data('_ExtendedFields', []),
        ['Wrap' => ['', '']]
    ); ?>
</ul>

<?php if (count($this->PermissionData) > 0) { ?>
    <section>
        <?php
        echo subheading(t('Check all permissions that apply to this role:'));
        if ($this->Role && $this->Role->CanSession != '1') { ?>
            <div class="alert alert-warning padded"><?php echo t('Heads Up! This is a special role that does not allow active sessions. For this reason, the permission options have been limited to "view" permissions.'); ?></div>
        <?php } ?>
        <div class="RolePermissions">
            <?php
            echo $this->Form->checkBoxGridGroups($this->PermissionData, 'Permission');
            ?>
        </div>
    </section>
<?php } ?>
<?php echo $this->Form->close('Save'); ?>
