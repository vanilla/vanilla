<?php if (!defined('APPLICATION')) exit();
// Make sure to force this form to post to the correct place in case the view is
// rendered within another view (ie. /dashboard/entry/index/):
?>
<h1><?php echo t("Sign In") ?></h1>
<div class="Box">
    <?php
    echo $this->Form->open(['Action' => url('/entry/handshake/'.$this->HandshakeScheme), 'id' => 'Form_User_Handshake']);
    echo $this->Form->errors();
    ?>
    <div class="Info"><?php
        // printf(
        //T('There is already an account with the same username (%1$s) or email (%2$s) as you. You can either create a new account, or you can enter the credentials for your existing forum account.'),
        echo wrap(t("This is the first time you've visited the discussion forums."), 'strong');
        echo wrap(t("You can either create a new account, or enter your credentials if you have an existing account."), 'div');
        // val('Name', $this->Data),
        // val('Email', $this->Data)
        // );
        ?></div>
    <ul class="NewAccount">
        <li><h2><?php echo t('Give me a new account'); ?></h2></li>
        <li>
            <?php
            echo $this->Form->label('Username', 'NewName');
            echo $this->Form->textbox('NewName');
            ?>
        </li>
        <li>
            <?php
            echo $this->Form->label('Email', 'NewEmail');
            echo $this->Form->textbox('NewEmail');
            ?>
        </li>
        <li class="Buttons">
            <?php echo $this->Form->button('Create New Account', ['Name' => 'User/NewAccount']); ?>
        </li>
    </ul>
    <ul class="LinkAccount">
        <li><h2><?php echo t('Link my existing account'); ?></h2></li>
        <li>
            <?php
            echo $this->Form->label('Email', 'SignInEmail');
            echo $this->Form->textBox('SignInEmail');
            ?>
        </li>
        <li>
            <?php
            echo $this->Form->label('Password', 'SignInPassword');
            echo $this->Form->input('SignInPassword', 'password');
            ?>
        </li>
        <li class="Buttons">
            <?php echo $this->Form->button('Link Existing Account', ['Name' => 'User/LinkAccount']); ?>
        </li>
    </ul>
    <?php
    // echo $this->Form->button("Get me outta here!", array('Name' => 'User/StopLinking'));
    echo $this->Form->close();
    ?>
</div>
