<?php if (!defined('APPLICATION')) exit(); ?>
    <h1><?php
        if ($this->data('User'))
            echo t('Edit User');
        else
            echo t('Add User');
        ?></h1>
<?php
echo $this->Form->open(array('class' => 'User'));
echo $this->Form->errors();
if ($this->data('AllowEditing')) { ?>
    <ul>
        <li class="form-group row">
            <div class="label-wrap">
                <?php echo $this->Form->label('Username', 'Name'); ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->textBox('Name'); ?>
            </div>
        </li>
        <li class="form-group row">
            <div class="label-wrap">
                <?php echo $this->Form->label('Email', 'Email');
                if (UserModel::noEmail()) {
                    echo '<div class="input">',
                    t('Email addresses are disabled.', 'Email addresses are disabled. You can only add an email address if you are an administrator.'),
                    '</div>';
                } ?>
            </div>
            <div class="input-wrap">
                <?php
                $EmailAttributes = [];

                // Email confirmation
                if (!$this->data('_EmailConfirmed'))
                    $EmailAttributes['class'] = 'Unconfirmed';

                echo $this->Form->textBox('Email', $EmailAttributes); ?>
            </div>
        </li>

        <?php if ($this->data('_CanConfirmEmail')): ?>
            <li class="User-ConfirmEmail form-group row">
                <div class="input-wrap no-label">
                    <?php echo $this->Form->CheckBox('ConfirmEmail', t("Email is confirmed"), array('value' => '1')); ?>
                </div>
            </li>
        <?php endif ?>
        <li class="form-group row">
            <div class="input-wrap no-label">
                <?php echo $this->Form->CheckBox('ShowEmail', t('Email visible to other users'), array('value' => '1')); ?>
            </div>
        </li>
        <li class="form-group row">
            <div class="input-wrap no-label">
                <?php echo $this->Form->CheckBox('Verified', t('Verified Label', 'Verified. Bypasses spam and pre-moderation filters.'), array('value' => '1')); ?>
            </div>
        </li>
        <li class="form-group row">
            <div class="input-wrap no-label">
                <?php echo $this->Form->CheckBox('Banned', t('Banned'), array('value' => $this->data('BanFlag'))); ?>
                <?php if ($this->data('BannedOtherReasons')): ?>
                <div class="text-danger info"><?php echo t(
                        'This user is also banned for other reasons and may stay banned.',
                        'This user is also banned for other reasons and may stay banned or become banned again.'
                    )?></div>
                <?php endif; ?>
            </div>
        </li>

        <?php if (c('Garden.Profile.Locations', false)): ?>
            <li class="form-group row User-Location">
                <div class="label-wrap">
                    <?php echo $this->Form->label('Location', 'Location'); ?>
                </div>
                <div class="input-wrap">
                    <?php echo $this->Form->textBox('Location'); ?>
                </div>
            </li>
        <?php endif; ?>

        <?php if (c('Garden.Profile.Titles', false)): ?>
            <li class="form-group row User-Title">
                <div class="label-wrap">
                    <?php echo $this->Form->label('Title', 'Title'); ?>
                </div>
                <div class="input-wrap">
                    <?php echo $this->Form->textBox('Title'); ?>
                </div>
            </li>
        <?php endif; ?>

        <?php
        $this->fireEvent('CustomUserFields')
        ?>

    <?php if (count($this->data('Roles'))) : ?>
        <li class="form-group row">
            <div class="label-wrap">
                <?php echo t('Check all roles that apply to this user:'); ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->CheckBoxList("RoleID", array_flip($this->data('Roles')), array_flip($this->data('UserRoles'))); ?>
            </div>
        </li>
    <?php endif; ?>
        <li class="PasswordOptions form-group row">
            <div class="label-wrap">
                <?php echo t('Password Options'); ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->RadioList('ResetPassword', $this->ResetOptions); ?>
            </div>
        </li>
        <?php if (array_key_exists('Manual', $this->ResetOptions)) : ?>
            <li id="NewPassword" class="form-group row">
                <div class="label-wrap">
                    <?php echo $this->Form->label('New Password', 'NewPassword'); ?>
                </div>
                <div class="input-wrap">
                    <?php echo $this->Form->Input('NewPassword', 'password'); ?>
                </div>
            </li>
            <li class="form-group row">
                <div class="buttons input-wrap no-label">
                    <?php
                    echo anchor(t('Generate Password'), '#', 'GeneratePassword btn btn-secondary');
                    echo anchor(t('Reveal Password'), '#', 'RevealPassword btn btn-secondary');
                    ?>
                </div>
            </li>
        <?php endif; ?>
    </ul>
    <?php

    $this->fireEvent('AfterFormInputs');
    echo $this->Form->close('Save');
}
