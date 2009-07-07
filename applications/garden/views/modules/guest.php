<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Box GuestBox">
   <h4><?php echo Gdn::Translate('Howdy, Stranger!'); ?></h4>
   <p><?php echo Translate($this->MessageCode); ?></p>
   <p>
      <?php echo Anchor('Sign In', Gdn::Authenticator()->SignInUrl($this->_Sender->SelfUrl), 'Button'); ?> 
      <?php
         $Url = Gdn::Authenticator()->RegisterUrl($this->_Sender->SelfUrl);
         if(!empty($Url))
            echo Anchor('Register For Membership', $Url, 'Button');
      ?>
   </p>
</div>