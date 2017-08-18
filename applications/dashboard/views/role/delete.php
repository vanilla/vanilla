<?php if (!defined('APPLICATION')) exit(); ?>
    <h1><?php echo t('Delete Role'); ?></h1>
<?php
echo $this->Form->open();
echo $this->Form->errors();
?>
    <div class="alert alert-danger padded-top"><?php echo t("<strong>Heads Up!</strong> Deleting a role can result in users not having access to the application."); ?></div>

    <div class="padded-bottom affected-users ">
        <p><?php printf(t("%s user(s) will be affected by this action."), $this->AffectedUsers); ?></p>
    <?php
    if ($this->OrphanedUsers > 0) {
        echo '<p>'.sprintf(t("If you delete this role and don't specify a replacement role, %s user(s) will be orphaned."), $this->OrphanedUsers).'</p>';
    }
    ?>
    </div>
    <ul>
        <li class="form-group">
            <div class="label-wrap">
                <?php echo $this->Form->label('Replacement Role', 'ReplacementRoleID'); ?>
                <div class="info"><?php echo t('Choose a role that orphaned users will be assigned to.'); ?></div>
            </div>
            <div class="input-wrap">
                <?php  echo $this->Form->dropDown(
                    'ReplacementRoleID',
                    $this->ReplacementRoles,
                    [
                        'ValueField' => 'RoleID',
                        'TextField' => 'Name',
                        'IncludeNull' => true
                    ]);
                ?>
            </div>
        </li>
    </ul>
<?php echo $this->Form->close('Delete');
