<?php if (!defined('APPLICATION')) exit();
// Make sure to force this form to post to the correct place in case the view is
// rendered within another view (ie. /garden/entry/index/):
echo $this->Form->Open(array('Action' => Url('/entry/signin'), 'id' => 'Form_User_SignIn'));
?>
<h1><?php echo Translate("Sign in") ?></h1>
<?php echo $this->Form->Errors(); ?>
<ul>
   <li>
      <?php
         echo $this->Form->Label('Username', 'Name');
         echo $this->Form->TextBox('Name');
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
         echo $this->Form->CheckBox('RememberMe', 'Remember me on this computer', array('value' => '1', 'id' => 'SignInRememberMe'));
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->Button('Sign Me In!');
      ?>
   </li>
</ul>
<?php echo $this->Form->Close();