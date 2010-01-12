<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo Translate("Apply for membership") ?></h1>
<div class="Box">
   <?php
   $TermsOfServiceUrl = Gdn::Config('Garden.TermsOfService', '#');
   $TermsOfServiceText = sprintf(Gdn::Translate('I agree to the <a id="TermsOfService" class="Popup" target="terms" href="%s">terms of service</a>'), Url($TermsOfServiceUrl));
   
   // Make sure to force this form to post to the correct place in case the view is
   // rendered within another view (ie. /garden/entry/index/):
   echo $this->Form->Open(array('Action' => Url('/entry/register'), 'id' => 'Form_User_Register'));
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
      <li>
         <?php
            echo $this->Form->Label('Why do you want to join?', 'DiscoveryText');
            echo $this->Form->TextBox('DiscoveryText', array('MultiLine' => TRUE));
         ?>
      </li>
      <li>
         <?php
            echo $this->Form->CheckBox('TermsOfService', $TermsOfServiceText, array('value' => '1'));
         ?>
      </li>
      <li>
         <?php echo $this->Form->Button('Apply →'); ?>
      </li>
   </ul>
   <?php echo $this->Form->Close(); ?>
</div>