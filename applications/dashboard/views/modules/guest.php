<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Box GuestBox">
    <h4><?php echo t('Howdy, Stranger!'); ?></h4>

    <p><?php echo t($this->MessageCode, $this->MessageDefault); ?></p>

    <p><?php $this->fireEvent('BeforeSignInButton'); ?></p>

    <?php
    $signInUrl = SignInUrl($this->_Sender->SelfUrl);

    if ($signInUrl) {
        echo '<div class="P">';

        echo anchor(t('Sign In'), SignInUrl($this->_Sender->SelfUrl), 'Button Primary'.(SignInPopup() ? ' SignInPopup' : ''), array('rel' => 'nofollow'));
        $Url = RegisterUrl($this->_Sender->SelfUrl);
        if (!empty($Url))
            echo ' '.anchor(t('Register', t('Apply for Membership', 'Register')), $Url, 'Button ApplyButton', array('rel' => 'nofollow'));

        echo '</div>';
    }
    ?>
    <?php $this->fireEvent('AfterSignInButton'); ?>
</div>
