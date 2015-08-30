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
        <li>
            <?php
            echo $this->Form->label('Username', 'Name');
            echo $this->Form->textBox('Name');
            ?>
        </li>
        <li>
            <?php

            echo $this->Form->label('Email', 'Email');
            if (UserModel::noEmail()) {
                echo '<div class="Gloss">',
                t('Email addresses are disabled.', 'Email addresses are disabled. You can only add an email address if you are an administrator.'),
                '</div>';
            }

            $EmailAttributes = array();

            // Email confirmation
            if (!$this->data('_EmailConfirmed'))
                $EmailAttributes['class'] = 'InputBox Unconfirmed';

            echo $this->Form->textBox('Email', $EmailAttributes);
            ?>
        </li>
        <?php if ($this->data('_CanConfirmEmail')): ?>
            <li class="User-ConfirmEmail">
                <?php
                echo $this->Form->CheckBox('ConfirmEmail', t("Email is confirmed"), array('value' => '1'));
                ?>
            </li>
        <?php endif ?>
        <li>
            <?php
            echo $this->Form->CheckBox('ShowEmail', t('Email visible to other users'), array('value' => '1'));
            ?>
        </li>
        <li>
            <?php
            echo $this->Form->CheckBox('Verified', t('Verified Label', 'Verified. Bypasses spam and pre-moderation filters.'), array('value' => '1'));
            ?>
        </li>
        <li>
            <?php
            echo $this->Form->CheckBox('Banned', t('Banned'), array('value' => '1'));
            ?>
        </li>
        <?php
        $this->fireEvent('CustomUserFields')
        ?>
    </ul>

    <?php if (count($this->data('Roles'))) : ?>
        <h3><?php echo t('Roles'); ?></h3>
        <ul>
            <li>
                <strong><?php echo t('Check all roles that apply to this user:'); ?></strong>
                <?php
                //echo $this->Form->CheckBoxList("RoleID", $this->RoleData, $this->UserRoleData, array('TextField' => 'Name', 'ValueField' => 'RoleID'));
                echo $this->Form->CheckBoxList("RoleID", array_flip($this->data('Roles')), array_flip($this->data('UserRoles')));
                ?>
            </li>
        </ul>
    <?php endif; ?>

    <h3><?php echo t('Password Options'); ?></h3>
    <ul>
        <li class="PasswordOptions">
            <?php
            echo $this->Form->RadioList('ResetPassword', $this->ResetOptions);
            ?>
        </li>
        <?php if (array_key_exists('Manual', $this->ResetOptions)) : ?>
            <li id="NewPassword">
                <?php
                echo $this->Form->label('New Password', 'NewPassword');
                echo $this->Form->Input('NewPassword', 'password');
                ?>
                <div class="InputButtons">
                    <?php
                    echo anchor(t('Generate Password'), '#', 'GeneratePassword Button SmallButton');
                    echo anchor(t('Reveal Password'), '#', 'RevealPassword Button SmallButton');
                    ?>
                </div>
            </li>
        <?php endif; ?>
    </ul>
    <?php

    $this->fireEvent('AfterFormInputs');
    echo $this->Form->close('Save');
}
