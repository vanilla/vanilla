<?php if (!defined('APPLICATION')) exit();
$confirmationSupported = $this->data('ConfirmationSupported');
helpAsset(sprintf(t('About %s'), t('Registration')), t('Change the way that new users register with the site.'));
helpAsset(t('Need More Help?'), anchor(t("Video tutorial on user registration"), 'settings/tutorials/user-registration'))
?>
<h1><?php echo t('User Registration Settings'); ?></h1>
<?php
echo $this->Form->open();
echo $this->Form->errors(); ?>

<?php if (UserModel::noEmail()) {
    echo '<div class="alert alert-danger padded-top">'.
        t('Email addresses are disabled.', 'Email addresses are disabled. You can only add an email address if you are an administrator.').
        '</div>';
} ?>
<?php if (!$confirmationSupported) {
    echo '<div class="alert alert-warning padded-top">'.
        t('No unconfirmed role available for email confirmation.', 'The site needs a role with default type "unconfirmed" to use email confirmation. Please add one to enable this setting.').
        '</div>';
} ?>
<div class="form-group<?php echo !$confirmationSupported ? ' foggy' : ''; ?>">
    <?php
    $label = t('Confirm email addresses', 'Require users to confirm their email addresses (recommended)');
    echo $this->Form->toggle('Garden.Registration.ConfirmEmail', $label);
    ?>
</div>

<div id="RegistrationMethods">
    <div class="table-wrap">
        <table class="Label table-data js-tj">
            <thead>
            <tr>
                <th><?php echo t('Method'); ?></th>
                <th class="column-lg"><?php echo t('Description'); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php
            $Count = count($this->RegistrationMethods);
            $i = 0;
            $Alt = false;
            foreach ($this->RegistrationMethods as $Method => $Description) {
                $Alt = !$Alt;
                $CssClass = '';
                if ($Alt) {
                    $CssClass = 'Alt';
                }
                ++$i;
                if ($Count == $i)
                    $CssClass .= ' Last';

                $CssClass = trim($CssClass);
                ?>
                <tr<?php echo $CssClass != '' ? ' class="'.$CssClass.'"' : ''; ?>>
                    <th><?php
                        $MethodName = $Method;
                        echo $this->Form->radio('Garden.Registration.Method', $MethodName, ['value' => $Method]);
                        ?></th>
                    <td class="Alt"><?php echo t($Description); ?></td>
                </tr>
            <?php
            }
            ?>
            </tbody>
        </table>
    </div>
</div>

<?php Captcha::settings($this); ?>

<?php $this->fireEvent('RegistrationView'); ?>

<div id="InvitationExpiration" class="form-group">
    <div class="label-wrap">
    <?php echo $this->Form->label('Invitations will expire', 'Garden.Registration.InviteExpiration'); ?>
    </div>
    <div class="input-wrap">
    <?php echo $this->Form->dropDown('Garden.Registration.InviteExpiration', $this->InviteExpirationOptions, ['value' => $this->InviteExpiration]); ?>
    </div>
</div>
<div id="InvitationSettings">
    <?php
    echo '<div class="padded">'.t('Choose who can send out invitations to new members:').'</div>';
    ?>
    <div class="table-wrap">
    <table class="Label table-data js-tj">
        <thead>
        <tr>
            <th><?php echo t('Role'); ?></th>
            <th class="column-xl"><?php echo t('Invitations per month'); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php
        $i = 0;
        $Count = $this->RoleData->numRows();
        $Alt = false;
        foreach ($this->RoleData->result() as $Role) {
            $Alt = !$Alt;
            $CssClass = '';
            if ($Alt) {
                $CssClass = 'Alt';
            }
            ++$i;
            if ($Count == $i) {
                $CssClass .= ' Last';
            }

            $CssClass = trim($CssClass);
            $CurrentValue = val($Role['RoleID'], $this->ExistingRoleInvitations, false);
            ?>
            <tr<?php echo $CssClass != '' ? ' class="'.$CssClass.'"' : ''; ?>>
                <th><?php echo $Role['Name']; ?></th>
                <td class="Alt">
                    <?php
                    echo $this->Form->dropDown('InvitationCount[]', $this->InvitationOptions, ['value' => $CurrentValue]);
                    echo $this->Form->hidden('InvitationRoleID[]', ['value' => $Role['RoleID']]);
                    ?>
                </td>
            </tr>
        <?php
        }
        ?>
        </tbody>
    </table>
    </div>
</div>
<?php echo $this->Form->close('Save'); ?>
