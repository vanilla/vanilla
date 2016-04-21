<?php if (!defined('APPLICATION')) exit(); ?>
    <div class="Help Aside">
        <?php
        echo wrap(t('Need More Help?'), 'h2');
        echo '<ul>';
        echo wrap(anchor(t("Video tutorial on user registration"), 'settings/tutorials/user-registration'), 'li');
        echo '</ul>';
        ?>
    </div>
    <h1><?php echo t('User Registration Settings'); ?></h1>
<?php
echo $this->Form->open();
echo $this->Form->errors();

echo Gdn::slice('/dashboard/role/defaultroleswarning');

?>
    <ul>
        <li id="RegistrationMethods">
            <div class="Info"><?php echo t('Change the way that new users register with the site.'); ?></div>
            <table class="Label AltColumns">
                <thead>
                <tr>
                    <th><?php echo t('Method'); ?></th>
                    <th class="Alt"><?php echo t('Description'); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php
                $Count = count($this->RegistrationMethods);
                $i = 0;
                $Alt = false;
                foreach ($this->RegistrationMethods as $Method => $Description) {
                    $Alt = $Alt ? false : true;
                    $CssClass = $Alt ? 'Alt' : '';
                    ++$i;
                    if ($Count == $i)
                        $CssClass .= ' Last';

                    $CssClass = trim($CssClass);
                    ?>
                    <tr<?php echo $CssClass != '' ? ' class="'.$CssClass.'"' : ''; ?>>
                        <th><?php
                            $MethodName = $Method;
                            echo $this->Form->radio('Garden.Registration.Method', $MethodName, array('value' => $Method));
                            ?></th>
                        <td class="Alt"><?php echo t($Description); ?></td>
                    </tr>
                <?php
                }
                ?>
                </tbody>
            </table>
        </li>

        <?php Captcha::settings($this); ?>

        <?php $this->fireEvent('RegistrationView'); ?>

        <li id="InvitationExpiration">
            <?php
            echo $this->Form->label('Invitations will expire', 'Garden.Registration.InviteExpiration');
            echo $this->Form->dropDown('Garden.Registration.InviteExpiration', $this->InviteExpirationOptions, array('value' => $this->InviteExpiration));
            ?>
        </li>
        <li id="InvitationSettings">
            <div class="Info">
                <?php
                echo sprintf(t('Invitations can be sent from users\' profile pages.',
                    'When you use registration by invitation users will have a link called <a href="%s" class="Popup">My Invitations</a> on their profile pages.'),
                    url('/dashboard/profile/invitations')),
                '<br /><br />';

                echo t('Choose who can send out invitations to new members:');
                ?>
            </div>
            <table class="Label AltColumns">
                <thead>
                <tr>
                    <th><?php echo t('Role'); ?></th>
                    <th class="Alt"><?php echo t('Invitations per month'); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php
                $i = 0;
                $Count = $this->RoleData->numRows();
                $Alt = false;
                foreach ($this->RoleData->result() as $Role) {
                    $Alt = $Alt ? false : true;
                    $CssClass = $Alt ? 'Alt' : '';
                    ++$i;
                    if ($Count == $i)
                        $CssClass .= ' Last';

                    $CssClass = trim($CssClass);
                    $CurrentValue = val($Role['RoleID'], $this->ExistingRoleInvitations, false);
                    ?>
                    <tr<?php echo $CssClass != '' ? ' class="'.$CssClass.'"' : ''; ?>>
                        <th><?php echo $Role['Name']; ?></th>
                        <td class="Alt">
                            <?php
                            echo $this->Form->DropDown('InvitationCount[]', $this->InvitationOptions, array('value' => $CurrentValue));
                            echo $this->Form->Hidden('InvitationRoleID[]', array('value' => $Role['RoleID']));
                            ?>
                        </td>
                    </tr>
                <?php
                }
                ?>
                </tbody>
            </table>
        </li>
        <li>
            <div class="Info">
                <?php
                if (UserModel::noEmail()) {
                    echo '<div class="Warning">',
                    t('Email addresses are disabled.', 'Email addresses are disabled. You can only add an email address if you are an administrator.'),
                    '</div>';
                }

                echo $this->Form->checkBox('Garden.Registration.ConfirmEmail', '@'.t('Confirm email addresses', 'Require users to confirm their email addresses (recommended)'));
                ?>
            </div>
        </li>
    </ul>
<?php echo $this->Form->close('Save');
