<?php if (!defined('APPLICATION')) exit(); ?>
<div class="FormTitleWrapper">
    <h1 class="H"><?php echo t('Change My Password'); ?></h1>
    <?php
    echo $this->Form->open();
    echo $this->Form->errors();
    ?>
    <ul>
        <li>
            <?php
            // No password may have been set if they have only signed in with a connect plugin
            if (!$this->User->HashMethod || $this->User->HashMethod == "Vanilla") {
                echo $this->Form->label('Old Password', 'OldPassword');
                echo $this->Form->input('OldPassword', 'password');
            }
            ?>
        </li>
        <li>
            <?php
            echo $this->Form->label('New Password', 'Password');
            echo $this->Form->input('Password', 'password', ['Strength' => TRUE]);
            ?>
        </li>
        <li>
            <?php
            echo $this->Form->label('Confirm Password', 'PasswordMatch');
            echo $this->Form->input('PasswordMatch', 'password');
            ?>
        </li>
    </ul>
    <?php echo $this->Form->close('Change Password', '', ['class' => 'Button Primary']); ?>
</div>
