<?php if (!defined('APPLICATION')) exit(); ?>
<div class="FormTitleWrapper AjaxForm">
    <h1><?php echo t("Recover Password") ?></h1>
    <div class="FormWrapper">
        <?php
        // Make sure to force this form to post to the correct place in case the view is
        // rendered within another view (ie. /dashboard/entry/index/):
        echo $this->Form->open(['Action' => url('/entry/passwordrequest?'.http_build_query($this->Request->get())), 'id' => 'Form_User_Password']);
        echo $this->Form->errors(); ?>
        <ul>
            <li>
                <?php
                echo $this->Form->label($this->data('RecoverPasswordLabelCode', 'Enter your email to continue.'), 'Email');
                echo $this->Form->textBox('Email', ['autofocus' => 'autofocus']);
                ?>
            </li>
            <li class="Buttons">
                <?php
                echo $this->Form->button('Request a new password', ['class' => 'Button Primary']);
                echo anchor(t('I remember now!'), '/entry/signin', 'ForgotPassword');
                ?>
            </li>
        </ul>
        <?php echo $this->Form->close(); ?>
    </div>
</div>
