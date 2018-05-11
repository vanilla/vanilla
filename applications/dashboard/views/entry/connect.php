<?php if (!defined('APPLICATION')) exit();

// Get the connection information.
if (!($ConnectName = $this->Form->getFormValue('FullName'))) {
    $ConnectName = $this->Form->getFormValue('Name');
}
$ConnectPhoto = $this->Form->getFormValue('Photo');
if (!$ConnectPhoto) {
    $ConnectPhoto = '/applications/dashboard/design/images/usericon.gif';
}
$ConnectSource = $this->Form->getFormValue('ProviderName');

// By default, clients will try to connect existing users.
// Turning this off forces connecting clients to choose unique usernames.
$allowConnect = $this->data('AllowConnect');

$hasUserID = $this->Form->getFormValue('UserID');

if (!$hasUserID) {
    // Determine whether to show ConnectName field.
    $ExistingUsers = (array)$this->data('ExistingUsers', []);
    $NoConnectName = $this->data('NoConnectName');

    // You just landed on this page.
    $firstTimeHere = !($this->Form->isPostBack() && $this->Form->getFormValue('Connect', null) !== null);
    $connectNameProvided = (bool)$this->Form->getFormValue('ConnectName');

    // Buckle up, deciding whether to show this field is intense.
    // Any of these 3 scenarios will do it:
    $displayConnectName =
        // 1) If you arrived with NO ConnectName OR you've clicked Submit WITH a ConnectName (not not both!)
        //    we need to display the field again so that you can add/edit it.
        ($firstTimeHere xor $connectNameProvided)
        // 2) If you clicked submit and we found matches (but validation failed and you need to try again).
        || (!$firstTimeHere && count($ExistingUsers))
        // 3) We're forcing a manual username selection.
        || !$allowConnect;
}
?>
<div class="Connect">
    <h1><?php echo stringIsNullOrEmpty($ConnectSource) ? t("Sign In") : sprintf(t('%s Connect'), htmlentities($ConnectSource)); ?></h1>

    <div>
        <?php
        echo $this->Form->open();
        echo $this->Form->errors();
        if ($ConnectName || $ConnectPhoto) : ?>
            <div class="MeBox">
                <?php
                if ($ConnectPhoto) {
                    echo '<span class="PhotoWrap">',
                    img($ConnectPhoto, ['alt' => t('Profile Picture'), 'class' => 'ProfilePhoto']),
                    '</span>';
                }

                echo '<div class="WhoIs">';
                if ($ConnectName && $ConnectSource) {
                    $NameFormat = t('You are connected as %s through %s.');
                } elseif ($ConnectName) {
                    $NameFormat = t('You are connected as %s.');
                } elseif ($ConnectSource) {
                    $NameFormat = t('You are connected through %2$s.');
                } else {
                    $NameFormat = '';
                }

                $NameFormat = '%1$s';
                echo sprintf(
                    $NameFormat,
                    '<span class="Name">'.htmlspecialchars($ConnectName).'</span>',
                    '<span class="Source">'.htmlspecialchars($ConnectSource).'</span>');

                echo wrap(t('ConnectCreateAccount', 'Add Info &amp; Create Account'), 'h3');

                echo '</div>';
                ?>
            </div>
        <?php endif; ?>

        <?php if ($hasUserID) : ?>
            <div class="SignedIn">
                <?php echo '<div class="Info">', t('You are now signed in.'), '</div>'; ?>
            </div>
        <?php else : ?>
            <ul>
                <?php if ($this->Form->getFormValue('EmailVisible')) : ?>
                <li>
                    <?php
                    echo $this->Form->label('Email', 'Email');
                    echo $this->Form->textBox('Email');
                    ?>
                </li>
                <?php endif; ?>

                <?php
                    $PasswordMessage = t('ConnectLeaveBlank', 'Leave blank unless connecting to an existing account.');
                    if ($displayConnectName) : ?>
                <li>
                    <?php

                    if (count($ExistingUsers) == 1 && $NoConnectName) {
                        $PasswordMessage = t('ConnectExistingPassword', 'Enter your existing account password.');
                        $Row = reset($ExistingUsers);

                        echo '<div class="FinePrint">', t('ConnectAccountExists', 'You already have an account here.'), '</div>',
                        wrap(sprintf(t('ConnectRegisteredName', 'Your registered username: <strong>%s</strong>'), htmlspecialchars($Row['Name'])), 'div', ['class' => 'ExistingUsername']);
                        $this->addDefinition('NoConnectName', true);
                        echo $this->Form->hidden('UserSelect', ['Value' => $Row['UserID']]);
                    } else {
                        echo $this->Form->label('Username', 'ConnectName');
                        echo '<div class="FinePrint">', t('ConnectChooseName', 'Choose a name to identify yourself on the site.'), '</div>';

                        if (count($ExistingUsers) > 0) {
                            foreach ($ExistingUsers as $Row) {
                                echo wrap($this->Form->radio('UserSelect', $Row['Name'], ['value' => $Row['UserID']]), 'div');
                            }
                            echo wrap($this->Form->radio('UserSelect', t('Other'), ['value' => 'other']), 'div');
                        }
                    }

                    if (!$NoConnectName) {
                        echo $this->Form->textbox('ConnectName');
                    }
                    ?>
                </li>
                <?php endif; ?>

                <?php $this->fireEvent('RegisterBeforePassword'); ?>
                <li id="ConnectPassword">
                    <?php
                    echo $this->Form->label('Password', 'ConnectPassword');
                    echo wrap($PasswordMessage, 'div', ['class' => 'FinePrint']);
                    echo $this->Form->input('ConnectPassword', 'password');
                    ?>
                </li>
            </ul>

            <?php
            echo '<div class="Buttons">', wrap($this->Form->button('Connect', ['class' => 'Button Primary']), 'div', ['class' => 'ButtonContainer']), '</div>';

        endif;

        echo $this->Form->close();
        ?>
    </div>
</div>
