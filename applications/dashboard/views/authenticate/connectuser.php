<?php if (!defined('APPLICATION')) { exit; }

use \Vanilla\Models\SSOData;
/** @var $this AuthenticateController */

$form = $this->getForm();

/** @var SSOData $ssoData */
$ssoData = $this->data('ssoData');

$existingUsers = $this->data('existingUsers');

$getMeBox = function($user, $isChecked) use ($form) {
    ob_start();
    $options = [
        'value' => $user['userID']
    ];
    if ($isChecked) {
        $options['checked'] = 'checked';
    }
    ?>
    <div class="MeBox">
        <?php echo $form->radio('linkUserID', '', $options); ?>
        <span class="PhotoWrap">
            <?php echo img($user['photoUrl'], ['alt' => t('Profile Picture'), 'class' => 'ProfilePhoto']); ?>
        </span>
        <div class="WhoIs">
            <span class="Username"><?php echo htmlspecialchars($user['name']); ?></span>
            <?php // It may be nice to show a masked email here like: ex***le@g****.com ?>
        </div>
    </div>
    <?php
    $content = ob_get_contents();
    ob_end_clean();

    return $content;
};
?>
<div class="connectBox">
    <h1><?php printf(t('%s Connect'), $ssoData->getAuthenticatorType()); ?></h1>
    <div>
<?php
        echo $form->open();
        echo $form->errors();
?>
        <ul>
            <li>
<?php
            echo $form->label('Connect options');
            echo $form->radio('connectOption', t('Connect to an existing user account.'), ['value' => 'linkuser', 'checked' => 'checked']);
            echo $form->radio('connectOption', t('Create a new user account.'), ['value' => 'createuser']);
?>
            </li>
            <li id="linkuser" class="<?php echo ($form->getFormValue('connectOption', 'linkuser') !== 'linkuser' ? 'Hidden ' : ''); ?>js-connectOption">
                <ul>
                    <li>
<?php
                    // Extra form options
                    if ($existingUsers) {
                        echo $form->label('Existing user account(s)', 'connectUserID');
?>
                        <ul>
<?php
                        $selectedUserID = $form->getFormValue('linkUserID', current($existingUsers)['userID']);
                        foreach ($existingUsers as $userData) {
                            echo wrap($getMeBox($userData['user'], $selectedUserID === $userData['userID']), 'li');
                        }

                        echo wrap($form->radio('linkUserID', 'Other', ['value' => '-1']), 'li');
                        $isOtherSelected = $form->getFormValue('linkUserID') === '-1';
?>
                        </ul>
                    </li>
                    <li id="linkUserInfo" <?php echo (!$isOtherSelected ? 'class="Hidden"' : ''); ?>>
                        <ul>
                            <li>
<?php
                    }
                            echo $form->label('Username', 'linkUserName');
                            echo $form->textbox('linkUserName');
?>
                            </li>
                            <li>
<?php
                            echo $form->label('Email', 'linkUserEmail');
                            echo $form->textBox('linkUserEmail');

                            // Extra form options
                            if ($existingUsers) {
?>
                            </li>
                        </ul>
<?php
                            }
?>
                    </li>
                    <li>
<?php
                    echo $form->label('Password', 'linkUserPassword');
                    echo wrap(t('Enter the the password of the user account to validate that you own it.'), 'div', ['class' => 'FinePrint']);
                    echo $form->input('linkUserPassword', 'password');
?>
                    </li>
                </ul>
            </li>
            <li id="createuser" class="<?php echo ($form->getFormValue('connectOption', 'linkuser') === 'linkuser' ? 'Hidden ' : '')?>js-connectOption">
                <ul>
                    <li>
<?php
                    echo $form->label('Username', 'createUserName');
                    echo wrap(t('ConnectChooseName', 'Choose a name to identify yourself on the site.'), 'div', ['class' => 'FinePrint']);
                    echo $form->textbox('createUserName');
?>
                    </li>
                    <li>
<?php
                    echo $form->label('Email', 'createUserEmail');
                    echo $form->textBox('createUserEmail');
?>
                    </li>
                </ul>
            </li>
        </ul>
<?php

        /**
         * TODO: Handle extra registration fields.
         * The old event was RegisterBeforePassword which could have a better name and was assumed to be coming from the entryController.
         *
         * Todd: I'd prefer custom registration fields to be "done right" with some sort of data object.
         *       Failing that I'd leave the event out and task the extra fields for later.
         */

        echo '<div class="Buttons">';
        echo wrap($form->button('Connect', ['class' => 'Button Primary']), 'div', ['class' => 'ButtonContainer']);
        echo $form->close();
?>
        </div>
    </div>
</div>
