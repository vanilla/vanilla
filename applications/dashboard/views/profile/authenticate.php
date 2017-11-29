<?php if (!defined('APPLICATION')) exit(); ?>
<div class="FormTitleWrapper">
    <h1 class="H"><?php echo t('Enter Your Password'); ?></h1>
    <p class="DismissMessage WarningMessage">
        <?php echo t('Enter your password to continue.', 'You are attempting to perform a potentially sensitive operation. Please enter your password to continue.'); ?>
    </p>
    <?php
    echo $this->Form->open();
    echo $this->Form->errors();
    ?>
    <ul>
        <li>
            <?php
            echo $this->Form->label('Password', 'AuthenticatePassword');
            echo $this->Form->input('AuthenticatePassword', 'password');
            ?>
        </li>
    </ul>
    <?php echo $this->Form->close('Confirm', '', ['class' => 'Button Primary']); ?>
</div>
