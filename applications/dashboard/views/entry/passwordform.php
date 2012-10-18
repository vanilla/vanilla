<?php if (!defined('APPLICATION')) exit(); ?>
<div>
   <?php
   // Make sure to force this form to post to the correct place in case the view is
   // rendered within another view (ie. /dashboard/entry/index/):
   echo $this->Form->Open(array('Action' => $this->Data('FormUrl', Url('/entry/signin')), 'id' => 'Form_User_SignIn'));
   echo $this->Form->Errors();
   ?>
   <ul>
      <li>
         <?php
            echo $this->Form->Label(UserModel::SigninLabelCode(), 'Email');
            echo $this->Form->TextBox('Email', array('autofocus' => 'autofocus', 'autocorrect' => 'off', 'autocapitalize' => 'off', 'Wrap' => TRUE));
         ?>
      </li>
      <li>
         <?php
            echo $this->Form->Label('Password', 'Password');
            echo $this->Form->Input('Password', 'password', array('class' => 'InputBox Password'));
            echo Anchor(T('Forgot?'), '/entry/passwordrequest', 'ForgotPassword');
         ?>
      </li>
      <li class="Buttons">
         <?php
            echo $this->Form->Button('Sign In', array('class' => 'Button Primary'));
            echo $this->Form->CheckBox('RememberMe', T('Keep me signed in'), array('value' => '1', 'id' => 'SignInRememberMe'));
         ?>
      </li>
      <?php if (strcasecmp(C('Garden.Registration.Method'), 'Connect') != 0): ?>
      <li class="CreateAccount">
         <?php
            $Target = $this->Target();
            if ($Target != '')
               $Target = '?Target='.urlencode($Target);

            printf(T("Don't have an account? %s"), Anchor(T('Create One.'), '/entry/register'.$Target));
         ?>
      </li>
      <?php endif; ?>
   </ul>
   <?php
   echo $this->Form->Close();
   echo $this->Form->Open(array('Action' => Url('/entry/passwordrequest'), 'id' => 'Form_User_Password', 'style' => 'display: none;'));
   ?>
   <ul>
      <li>
         <?php
            echo $this->Form->Label('Enter your Email address or username', 'Email');
            echo $this->Form->TextBox('Email');
         ?>
      </li>
      <li class="Buttons">
         <?php
            echo $this->Form->Button('Request a new password', array('class' => 'Button Primary'));
            echo Anchor(T('I remember now!'), '/entry/signin', 'ForgotPassword');
         ?>
      </li>
   </ul>
   <?php echo $this->Form->Close(); ?>
</div>