<?php if (!defined('APPLICATION')) exit();
$editing = $this->data('User', false);
echo heading($editing ? t('Edit User') : t('Add User'));

/** @var Gdn_Form $form */
$form = $this->Form;
// autocomplete is set to "nothanks". This is, strangely, not a mistake. See:
// https://developer.mozilla.org/en-US/docs/Web/Security/Securing_your_site/Turning_off_form_autocompletion
echo $form->open(['class' => 'User', 'autocomplete' => 'nothanks']);
echo $form->errors();
?>
<ul>
    <li class="form-group">
        <?php echo $form->labelWrap('Username', 'Name'); ?>
        <?php echo $form->textBoxWrap('Name'); ?>
    </li>
    <li class="form-group">
        <div class="label-wrap">
            <?php echo $form->label('Email', 'Email');
            if (UserModel::noEmail()) {
                echo '<div class="info">',
                t('Email addresses are disabled.', 'Email addresses are disabled. You can only add an email address if you are an administrator.'),
                '</div>';
            }
            // Email confirmation
            if ($editing && !$this->data('_EmailConfirmed')) {
                echo '<div class="info text-warning">',
                t('This user has not confirmed their email address.'),
                '</div>';
            }
            ?>
        </div>
        <?php echo $form->textBoxWrap('Email'); ?>
    </li>

    <?php if ($this->data('_CanConfirmEmail')): ?>
        <li class="User-ConfirmEmail form-group">
            <div class="input-wrap no-label">
                <?php echo $form->checkBox('ConfirmEmail', t("Email is confirmed"), ['value' => '1']); ?>
            </div>
        </li>
    <?php endif ?>
    <li class="form-group">
        <div class="input-wrap no-label">
            <?php echo $form->checkBox('ShowEmail', t('Email visible to other users'), ['value' => '1']); ?>
        </div>
    </li>
    <li class="form-group">
        <div class="input-wrap no-label">
            <?php echo $form->checkBox('Verified', t('Verified Label', 'Verified. Bypasses spam and pre-moderation filters.'), ['value' => '1']); ?>
        </div>
    </li>
    <?php
    // No need to ban a new user.
    if ($editing) : ?>
        <li class="form-group">
            <div class="input-wrap no-label">
                <?php echo $form->checkBox('Banned', t('Banned'), ['value' => $this->data('BanFlag')]); ?>
                <?php if ($this->data('BannedOtherReasons')): ?>
                    <div class="text-danger info"><?php echo t(
                            'This user is also banned for other reasons and may stay banned.',
                            'This user is also banned for other reasons and may stay banned or become banned again.'
                        )?></div>
                <?php endif; ?>
            </div>
        </li>
    <?php endif; ?>
    <?php if (c('Garden.Profile.Locations', false)) : ?>
        <li class="form-group User-Location">
            <?php echo $form->labelWrap('Location', 'Location'); ?>
            <?php echo $form->textBoxWrap('Location'); ?>
        </li>
    <?php endif; ?>
    <?php if (c('Garden.Profile.Titles', false)) : ?>
        <li class="form-group User-Title">
            <?php echo $form->labelWrap('Title', 'Title'); ?>
            <?php echo $form->textBoxWrap('Title'); ?>
        </li>
    <?php endif; ?>
    <?php $this->fireEvent('CustomUserFields') ?>
    <?php if (count($this->data('Roles'))) : ?>
        <li class="form-group">
            <div class="label-wrap">
                <?php echo wrap(t('Check all roles that apply to this user:'), 'span', ['class' => 'label']); ?>
            </div>
            <div class="input-wrap">
                <?php echo $form->checkBoxList("RoleID", array_flip($this->data('Roles')), array_flip($this->data('UserRoles'))); ?>
            </div>
        </li>
    <?php endif;
    // Edit a user's password.
    if ($editing) : ?>
        <li class="PasswordOptions form-group">
            <div class="label-wrap">
                <?php echo wrap(t('Password Options'), 'span', ['class' => 'label']); ?>
            </div>
            <div class="input-wrap">
                <?php echo $form->radioList('ResetPassword', $this->ResetOptions); ?>
            </div>
        </li>
        <?php if (array_key_exists('Manual', $this->ResetOptions)) : ?>
            <li id="NewPassword">
                <div class="form-group">
                    <?php echo $form->labelWrap('New Password', 'NewPassword'); ?>
                    <?php echo $form->inputWrap('NewPassword', 'password'); ?>
                </div>
                <div class="form-group">
                    <div class="buttons input-wrap no-label">
                        <?php
                        echo anchor(t('Generate Password'), '#', 'GeneratePassword btn btn-secondary');
                        echo anchor(t('Reveal Password'), '#', 'RevealPassword btn btn-secondary',
                            ['data-hide-text' => t('Hide Password'), 'data-show-text' => t('Reveal Password')]);
                        ?>
                    </div>
                </div>
            </li>
        <?php endif;
    // Add a new user's password
    else: ?>
        <div class="form-group">
            <?php echo $form->labelWrap('Password', 'Password'); ?>
            <?php echo $form->inputWrap('Password', 'password'); ?>
        </div>
        <div class="form-group">
            <div class="buttons input-wrap no-label">
                <?php
                echo anchor(t('Generate Password'), '#', 'GeneratePassword btn btn-secondary');
                echo anchor(t('Reveal Password'), '#', 'RevealPassword btn btn-secondary',
                    ['data-hide-text' => t('Hide Password'), 'data-show-text' => t('Reveal Password')]);
                ?>
            </div>
        </div>
    <?php endif; ?>
</ul>
<?php

$this->fireEvent('AfterFormInputs');
echo $form->close('Save');

