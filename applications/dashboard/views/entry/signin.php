<?php if (!defined('APPLICATION')) exit();
$Methods = $this->Data('Methods', array());
$SelectedMethod = $this->Data('SelectedMethod', array());
$CssClass = count($Methods) > 0 ? ' MultipleEntryMethods' : ' SingleEntryMethod';

// Testing
//$Methods['Facebook'] = array('Label' => 'Facebook', 'Url' => '#', 'ViewLocation' => 'signin');
//$Methods['Twitter'] = array('Label' => 'Twitter', 'Url' => '#', 'ViewLocation' => 'signin');

echo '<h1>'.$this->Data('Title').'</h1>';

// Make sure to force this form to post to the correct place in case the view is
// rendered within another view (ie. /dashboard/entry/index/):
echo $this->Form->Open(array('Action' => $this->Data('FormUrl', Url('/entry/signin')), 'id' => 'Form_User_SignIn'));
echo $this->Form->Errors();

echo '<div class="Entry'.$CssClass.'">';

   // Render the main signin form.
   echo '<div class="MainForm">';
   ?>
   <ul>
      <li>
         <?php
            echo $this->Form->Label('Email/Username', 'Email');
            echo $this->Form->TextBox('Email', array('autocorrect' => 'off', 'autocapitalize' => 'off', 'Wrap' => TRUE));
         ?>
      </li>
      <li>
         <?php
            echo $this->Form->Label('Password', 'Password');
            echo $this->Form->Input('Password', 'password', array('class' => 'InputBox Password'));
            echo Anchor(T('Forgot?'), '/entry/passwordrequest', 'ForgotPassword');
         ?>
      </li>
   </ul>
   <?php
   
//   echo $this->Data('MainForm');

   echo '</div>';

   // Render the buttons to select other methods of signing in.
   if (count($Methods) > 0) {
      echo '<div class="Methods">'
         .Wrap('<b>'.T('Or you can...').'</b>', 'div');

      foreach ($Methods as $Key => $Method) {
         $CssClass = 'Method Method_'.$Key;
         echo '<div class="'.$CssClass.'">',
            $Method['SignInHtml'],
            '</div>';
      }

      echo '</div>';
   }

echo '</div>';

?>
<div class="Buttons">
   <?php
      echo $this->Form->Button('Sign In', array('class' => 'Button Primary'));
      echo $this->Form->CheckBox('RememberMe', T('Keep me signed in'), array('value' => '1', 'id' => 'SignInRememberMe'));
   ?>
<?php if (strcasecmp(C('Garden.Registration.Method'), 'Connect') != 0): ?>
<div class="CreateAccount">
   <?php
      $Target = $this->Target();
      if ($Target != '')
         $Target = '?Target='.urlencode($Target);

      printf(T("Don't have an account? %s"), Anchor(T('Create One.'), '/entry/register'.$Target));
   ?>
</div>
<?php endif; ?>

</div>

<?php
echo $this->Form->Close();

// Password reset form.
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
<?php echo $this->Form->Close();