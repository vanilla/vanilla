<?php if (!defined('APPLICATION')) exit();

$TermsOfServiceUrl = Gdn::Config('Garden.TermsOfService', '#');
$TermsOfServiceText = sprintf(Gdn::Translate('I agree to the <a id="TermsOfService" class="Popup" target="terms" href="{0}">terms of service</a>'), Url($TermsOfServiceUrl));
$CaptchaPublicKey = Gdn::Config('Garden.Registration.CaptchaPublicKey');

// Make sure to force this form to post to the correct place in case the view is
// rendered within another view (ie. /garden/entry/index/):
echo $this->Form->Open(array('Action' => Url('/entry/register'), 'id' => 'Form_User_Register'));
?>
<h1><?php echo Translate("Apply for membership") ?></h1>
<?php echo $this->Form->Errors(); ?>
<ul>
   <li>
      <?php
         echo $this->Form->Label('Email', 'Email');
         echo $this->Form->TextBox('Email');
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->Label('Name', 'Name');
         echo $this->Form->TextBox('Name');
         echo '<span id="NameUnavailable" class="Incorrect" style="display: none;">'.Gdn::Translate('Name Unavailable').'</span>';
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
         echo $this->Form->Label('Confirm Password', 'PasswordMatch');
         echo $this->Form->Input('PasswordMatch', 'password');
         echo '<span id="PasswordsDontMatch" class="Incorrect" style="display: none;">'.Translate("Passwords don't match").'</span>';
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->Label('Birth Date', 'DateOfBirth', array('class' => 'BirthDate'));
         echo $this->Form->Date('DateOfBirth');
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->Label('Gender', 'Gender');
         echo $this->Form->RadioList('Gender', $this->GenderOptions, array('default' => 'm'))
      ?>
   </li>
   <li><?php
      echo recaptcha_get_html($CaptchaPublicKey);
   ?></li>
   <li>
      <?php
         echo $this->Form->CheckBox('TermsOfService', $TermsOfServiceText, array('value' => '1'));
         echo $this->Form->CheckBox('RememberMe', 'Remember me on this computer', array('value' => '1'));
      ?>
   </li>
   <li>
      <?php echo $this->Form->Button('Sign Up!'); ?>
   </li>
</ul>
<?php echo $this->Form->Close();