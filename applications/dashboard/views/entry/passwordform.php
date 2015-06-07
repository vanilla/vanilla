<?php if (!defined('APPLICATION')) exit(); ?>
<div>
    <?php
    // Make sure to force this form to post to the correct place in case the view is
    // rendered within another view (ie. /dashboard/entry/index/):
    echo $this->Form->open(array('Action' => $this->data('FormUrl', url('/entry/signin')), 'id' => 'Form_User_SignIn'));
    echo $this->Form->errors();
    ?>
    <ul>
        <li>
            <?php
            echo $this->Form->label(UserModel::SigninLabelCode(), 'Email');
            echo $this->Form->textBox('Email', array('autofocus' => 'autofocus', 'autocorrect' => 'off', 'autocapitalize' => 'off', 'Wrap' => TRUE));
            ?>
        </li>
        <li>
            <?php
            echo $this->Form->label('Password', 'Password');
            echo $this->Form->Input('Password', 'password', array('class' => 'InputBox Password'));
            echo anchor(t('Forgot?'), '/entry/passwordrequest', 'ForgotPassword');
            ?>
        </li>
        <li class="Buttons">
            <?php
            echo $this->Form->button('Sign In', array('class' => 'Button Primary'));
            echo $this->Form->CheckBox('RememberMe', t('Keep me signed in'), array('value' => '1', 'id' => 'SignInRememberMe'));
            ?>
        </li>
        <?php if (strcasecmp(c('Garden.Registration.Method'), 'Connect') != 0): ?>
            <li class="CreateAccount">
                <?php
                $Target = $this->target();
                if ($Target != '') {
                    $Target = '?Target='.urlencode($Target);
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
    echo $this->Form->open(array('Action' => url('/entry/passwordrequest'), 'id' => 'Form_User_Password', 'style' => 'display: none;'));
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
            echo $this->Form->button('Request a new password', array('class' => 'Button Primary'));
            echo anchor(t('I remember now!'), '/entry/signin', 'ForgotPassword');
            ?>
        </li>
    </ul>
    <?php echo $this->Form->close(); ?>
</div>
