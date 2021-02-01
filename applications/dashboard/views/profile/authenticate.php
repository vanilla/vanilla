<?php use Vanilla\Theme\BoxThemeShim;

if (!defined('APPLICATION')) exit(); ?>
<div class="FormTitleWrapper">
    <?php BoxThemeShim::startHeading(); ?>
    <h1 class="H"><?php echo t('Enter Your Password'); ?></h1>
    <?php BoxThemeShim::endHeading(); ?>
    <p class="DismissMessage AlertMessage">
        <?php echo t('Enter your password to continue.', 'You are attempting to perform a potentially sensitive operation. Please enter your password to continue.'); ?>
    </p>
    <?php
    BoxThemeShim::startBox();
        echo $this->Form->open();
        echo $this->Form->errors();
        ?>
        <ul role="presentation">
            <li role="presentation">
                <?php
                echo $this->Form->label('Password', 'AuthenticatePassword');
                echo $this->Form->input('AuthenticatePassword', 'password');
                ?>
            </li>
        </ul>
        <?php
        echo $this->Form->close('Confirm', '', ['class' => 'Button Primary']);
    BoxThemeShim::endBox();
    ?>
</div>
