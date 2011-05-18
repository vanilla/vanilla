<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Box GuestBox">
   <h4><?php echo T('Howdy, Stranger!'); ?></h4>
   <p><?php echo T($this->MessageCode, $this->MessageDefault); ?></p>
   <?php $this->FireEvent('BeforeSignInButton'); ?>
   
   <?php
   if (strcasecmp(C('Garden.Registration.Method'), 'Connect') != 0) {
      echo '<div class="P">';

      echo Anchor(T('Sign In'), SignInUrl($this->_Sender->SelfUrl), 'Button'.(SignInPopup() ? ' SignInPopup' : ''));
      $Url = RegisterUrl($this->_Sender->SelfUrl);
      if(!empty($Url))
         echo ' '.Anchor(T('Apply for Membership'), $Url, 'Button ApplyButton');

      echo '</div>';
   }
   ?>
   <?php $this->FireEvent('AfterSignInButton'); ?>
</div>