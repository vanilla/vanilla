<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Box GuestBox">
   <h4><?php echo T('Howdy, Stranger!'); ?></h4>
   <p><?php echo T($this->MessageCode, $this->MessageDefault); ?></p>
   <?php $this->FireEvent('BeforeSignInButton'); ?>
   
   <?php
   if (strcasecmp(C('Garden.Registration.Method'), 'Connect') != 0) {
      echo '<div class="P">';

      echo Anchor(T('Sign In'), SignInUrl($this->_Sender->SelfUrl), 'Button Primary'.(SignInPopup() ? ' SignInPopup' : ''), array('rel' => 'nofollow'));
      $Url = RegisterUrl($this->_Sender->SelfUrl);
      if(!empty($Url))
         echo ' '.Anchor(T('Register', T('Apply for Membership', 'Register')), $Url, 'Button ApplyButton', array('rel' => 'nofollow'));

      echo '</div>';
   }
   ?>
   <?php $this->FireEvent('AfterSignInButton'); ?>
</div>