<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo t('Sign In') ?></h1>
<div>
    <?php
    // Make sure to force this form to post to the correct place in case the view is
    // rendered within another view (ie. /dashboard/entry/index/):
    echo $this->Form->open(['Action' => $this->data('FormUrl', url('/entry/auth/password')), 'id' => 'Form_User_SignIn']);
    echo $this->Form->errors();
    ?>
    <ul>
        <li>
            <?php
            echo $this->Form->label(UserModel::signinLabelCode(), 'Email');
            echo $this->Form->textBox('Email');
            ?>
        </li>
        <li>
            <?php
            echo $this->Form->label('Password', 'Password');
            echo $this->Form->input('Password', 'password', ['class' => 'InputBox Password']);
            echo anchor(t('Forgot?'), '/entry/passwordrequest', 'ForgotPassword');
            ?>
        </li>
        <li class="Buttons">
            <?php
            echo $this->Form->button('Sign In');
            echo $this->Form->checkBox('RememberMe', t('Keep me signed in'), ['value' => '1', 'id' => 'SignInRememberMe']);
            ?>
        </li>
        <?php if (strcasecmp(c('Garden.Registration.Method'), 'Connect') != 0): ?>
            <li class="CreateAccount">
                <?php
                $Target = getIncomingValue('Target', '');
                if ($Target != '') {
                    $Target = '?Target='.$Target;
                }

                if (c('Garden.Registration.Method') != 'Invitation') {
                    printf(t("Don't have an account? %s"), anchor(t('Create One.'), '/entry/register'.$Target));
                }
                ?>
            </li>
        <?php endif; ?>
    </ul>
    <?php
    echo $this->Form->close();
    echo $this->Form->open(['Action' => url('/entry/passwordrequest'), 'id' => 'Form_User_Password', 'style' => 'display: none;']);
    ?>
    <ul>
        <li>
            <?php
            echo $this->Form->label('Enter your Email address or username', 'Email');
            echo $this->Form->textBox('Email');
            ?>
        </li>
        <li class="Buttons">
            <?php
            echo $this->Form->button('Request a new password');
            echo wrap(anchor(t('I remember now!'), '/entry/signin', 'ForgotPassword'), 'div');
            ?>
        </li>
    </ul>
    <?php echo $this->Form->close(); ?>
</div>
