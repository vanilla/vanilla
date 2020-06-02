<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Box GuestBox">
    <h4 class="GuestBox-title">
        <?php echo t('Howdy, Stranger!'); ?>
    </h4>
    <p class="GuestBox-message">
        <?php echo t($this->MessageCode, $this->MessageDefault); ?>
    </p>

    <p class="GuestBox-beforeSignInButton">
        <?php $this->fireEvent('BeforeSignInButton'); ?>
    </p>

    <?php
        if ($this->data('signInUrl')) {
            echo '<div class="P">';
            echo anchor(t('Sign In'), $this->data('signInUrl'), 'Button Primary'.(signInPopup() ? ' SignInPopup' : ''), ['rel' => 'nofollow']);

            if ($this->data('registerUrl')) {
                echo ' '.anchor(t('Register', t('Apply for Membership', 'Register')), $this->data('registerUrl'), 'Button ApplyButton', ['rel' => 'nofollow']);
            }

            echo '</div>';
        }
    ?>
    <?php $this->fireEvent('AfterSignInButton'); ?>
</div>
