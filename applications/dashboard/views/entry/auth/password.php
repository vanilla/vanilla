<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo T('Sign In') ?></h1>
<div class="Box">
   <?php
   // Make sure to force this form to post to the correct place in case the view is
   // rendered within another view (ie. /dashboard/entry/index/):
   echo $this->Form->Open(array('Action' => Url('/entry/signin'), 'id' => 'Form_User_SignIn'));
   echo $this->Form->Errors();
   ?>
   <ul>
      <li>
         <?php
            echo $this->Form->Label('Email', 'Email');
            echo $this->Form->TextBox('Email');
         ?>
      </li>
      <li>
         <?php
            echo $this->Form->Label('Password', 'Password');
            echo $this->Form->Input('Password', 'password');
         ?>
      </li>
      <li>
         <?php
            echo $this->Form->CheckBox('RememberMe', T('Remember me on this computer'), array('value' => '1', 'id' => 'SignInRememberMe'));
         ?>
      </li>
      <li class="Buttons">
         <?php
            echo $this->Form->Button('Sign In →');
            echo '<span>'.T('or').'</span>';
            $Target = GetIncomingValue('Target', '');
            if ($Target != '')
               $Target = '?Target='.$Target;
               
            echo Anchor(T('Apply for Membership'), '/entry/register'.$Target);
         ?>
      </li>
      <li>
         <?php
            echo Anchor(T('Forgot your password?'), '/entry/passwordrequest', 'ForgotPassword');
         ?>
      </li>
   </ul>
   <?php
   echo $this->Form->Close();
   echo $this->Form->Open(array('Action' => Url('/entry/passwordrequest'), 'id' => 'Form_User_Password', 'style' => 'display: none;'));
   ?>
   <ul>
      <li>
         <?php
            echo $this->Form->Label('Enter your Email address', 'Email');
            echo $this->Form->TextBox('Email');
         ?>
      </li>
      <li>
         <?php
            echo $this->Form->Button('Request a new password →');
         ?>
      </li>
   </ul>
   <?php echo $this->Form->Close(); ?>
</div>