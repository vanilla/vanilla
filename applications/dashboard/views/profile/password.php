<?php use Vanilla\Theme\BoxThemeShim;

if (!defined('APPLICATION')) exit(); ?>
<div class="FormTitleWrapper">
    <?php BoxThemeShim::startHeading(); ?>
    <h1 class="H"><?php echo t('Change My Password'); ?></h1>
    <?php BoxThemeShim::endHeading(); ?>
    <?php
    echo $this->Form->open();
    echo $this->Form->errors();
    ?>
    <ul role="presentation" class="pageBox">
        <li role="presentation">
            <?php
            // No password may have been set if they have only signed in with a connect plugin
            if (!$this->User->HashMethod || $this->User->HashMethod == "Vanilla") {
                echo $this->Form->label('Old Password', 'OldPassword');
                echo $this->Form->input('OldPassword', 'password');
            }
            ?>
        </li>
        <li role="presentation">
            <?php
            echo $this->Form->label('New Password', 'Password');
            echo $this->Form->input('Password', 'password', ['Strength' => TRUE]);
            ?>
        </li>
        <li role="presentation">
            <?php
            echo $this->Form->label('Confirm Password', 'PasswordMatch');
            echo $this->Form->input('PasswordMatch', 'password');
            ?>
        </li>
    </ul>
    <?php echo $this->Form->close('Change Password', '', ['class' => 'Button Primary']); ?>
</div>
