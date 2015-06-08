<?php if (!defined('APPLICATION')) exit();
// Get the information for displaying the connection information.
if (!($ConnectName = $this->Form->getFormValue('FullName')))
    $ConnectName = $this->Form->getFormValue('Name');

$ConnectPhoto = $this->Form->getFormValue('Photo');
if (!$ConnectPhoto) {
    $ConnectPhoto = '/applications/dashboard/design/images/usericon.gif';
}
$ConnectSource = $this->Form->getFormValue('ProviderName');
?>
<div class="Connect">
    <h1><?php echo stringIsNullOrEmpty($ConnectSource) ? t("Sign in") : sprintf(t('%s Connect'), $ConnectSource); ?></h1>

    <div>
        <?php
        echo $this->Form->open();
        echo $this->Form->errors();
        if ($ConnectName || $ConnectPhoto):
            ?>
            <div class="MeBox">
                <?php
                if ($ConnectPhoto) {
                    echo '<span class="PhotoWrap">',
                    img($ConnectPhoto, array('alt' => t('Profile Picture'), 'class' => 'ProfilePhoto')),
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

        <?php if ($this->Form->getFormValue('UserID')): ?>
            <div class="SignedIn">
                <?php echo '<div class="Info">', t('You are now signed in.'), '</div>'; ?>
            </div>
        <?php
        else:
            $ExistingUsers = (array)$this->data('ExistingUsers', array());
            $NoConnectName = $this->data('NoConnectName');
            $PasswordMessage = t('ConnectLeaveBlank', 'Leave blank unless connecting to an existing account.');
            ?>
            <ul>
                <?php if ($this->Form->getFormValue('EmailVisible')): ?>
                    <li>
                        <?php
                        echo $this->Form->label('Email', 'Email');
                        echo $this->Form->textBox('Email');
                        ?>
                    </li>
                <?php endif; ?>
                <li>
                    <?php
                    if (count($ExistingUsers) == 1 && $NoConnectName) {
                        $PasswordMessage = t('ConnectExistingPassword', 'Enter your existing account password.');
                        $Row = reset($ExistingUsers);
                        echo '<div class="FinePrint">', t('ConnectAccountExists', 'You already have an account here.'), '</div>',
                        wrap(sprintf(t('ConnectRegisteredName', 'Your registered username: <strong>%s</strong>'), htmlspecialchars($Row['Name'])), 'div', array('class' => 'ExistingUsername'));
                        $this->addDefinition('NoConnectName', true);
                        echo $this->Form->Hidden('UserSelect', array('Value' => $Row['UserID']));
                    } else {
                        echo $this->Form->label('Username', 'ConnectName');
                        echo '<div class="FinePrint">', t('ConnectChooseName', 'Choose a name to identify yourself on the site.'), '</div>';

                        if (count($ExistingUsers) > 0) {
                            foreach ($ExistingUsers as $Row) {
                                echo wrap($this->Form->Radio('UserSelect', $Row['Name'], array('value' => $Row['UserID'])), 'div');
                            }
                            echo wrap($this->Form->Radio('UserSelect', t('Other'), array('value' => 'other')), 'div');
                        }
                    }

                    if (!$NoConnectName)
                        echo $this->Form->Textbox('ConnectName');
                    ?>
                </li>
                <?php $this->fireEvent('RegisterBeforePassword'); ?>
                <li id="ConnectPassword">
                    <?php
                    echo $this->Form->label('Password', 'ConnectPassword');
                    echo wrap($PasswordMessage, 'div', array('class' => 'FinePrint'));
                    echo $this->Form->Input('ConnectPassword', 'password');
                    ?>
                </li>
            </ul>

            <?php
            echo '<div class="Buttons">', wrap($this->Form->button('Connect', array('class' => 'Button Primary')), 'div', array('class' => 'ButtonContainer')), '</div>';

        endif;

        echo $this->Form->close();
        ?>
    </div>
</div>
