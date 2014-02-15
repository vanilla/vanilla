<?php if (!defined('APPLICATION')) exit();
?>
<style type="text/css">
.Configuration {
   margin: 0 20px 20px;
   background: #f5f5f5;
   float: left;
}
.ConfigurationForm {
   padding: 20px;
   float: left;
}
#Content form .ConfigurationForm ul {
   padding: 0;
}
#Content form .ConfigurationForm input.Button {
   margin: 0;
}
.ConfigurationHelp {
   border-left: 1px solid #aaa;
   margin-left: 340px;
   padding: 20px;
}
.ConfigurationHelp strong {
    display: block;
}
.ConfigurationHelp img {
   width: 99%;
}
.ConfigurationHelp a img {
    border: 1px solid #aaa;
}
.ConfigurationHelp a:hover img {
    border: 1px solid #777;
}
input.CopyInput {
   font-family: monospace;
   color: #000;
   width: 240px;
   font-size: 12px;
   padding: 4px 3px;
}
#Form_Secret {
   width: 280px;
}
#Form_ApplicationID {
   width: 280px;
}
</style>
<h1><?php echo $this->Data('Title'); ?></h1>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<div class="Info">
   <?php echo T('Facebook Connect allows users to sign in using their Facebook account.', 'Facebook Connect allows users to sign in using their Facebook account. <b>You must register your application with Facebook for this plugin to work.</b>'); ?>
</div>
<div class="Configuration">
   <div class="ConfigurationForm">
      <ul>
         <li>
            <?php
               echo $this->Form->Label('Application ID', 'ApplicationID');
               echo $this->Form->TextBox('ApplicationID');
            ?>
         </li>
         <li>
            <?php
               echo $this->Form->Label('Application Secret', 'Secret');
               echo $this->Form->TextBox('Secret');
            ?>
         </li>
         <li>
            <?php
               echo $this->Form->CheckBox('UseFacebookNames', 'Use Facebook names for usernames.');
            ?>
         </li>
         <li>
            <?php
               echo $this->Form->CheckBox('SendConnectEmail', 'Send users a welcome email.');
            ?>
         </li>
         <li>
            <?php
               echo $this->Form->CheckBox('SocialSignIn', 'Enable Social Sign In');
            ?>
         </li>
         <li>
            <?php
               echo $this->Form->CheckBox('SocialReactions', "Enable Social Reactions.");
            ?>
         </li>
         <li>
            <?php
               echo $this->Form->CheckBox('SocialSharing', 'Enable automatic Social Share.');
            ?>
         </li>
      </ul>
      <?php echo $this->Form->Button('Save', array('class' => 'Button SliceSubmit')); ?>
   </div>
   <div class="ConfigurationHelp">
      <strong>How to set up Facebook Connect</strong>
      <p>In order to set up Facebook Connect, you must create an "application" in Facebook at: <a href="https://developers.facebook.com/apps">https://developers.facebook.com/apps</a></p>
      <p>
         When you create the Facebook application, you can choose what to enter in most fields, but make sure you enter the following value in the "Site Url" field:
         <input type="text" class="CopyInput" value="<?php echo rtrim(Gdn::Request()->Domain(), '/').'/'; ?>" />
      </p>
      <p>Once your application has been set up, you must copy the "Application ID" and "Application Secret" into the form on this page and click save.</p>
      <strong>Need help?</strong>
      <p>For a complete walk-through of the steps involved, read <a href="http://blog.vanillaforums.com/facebook-application-for-vanillaforums-sso/">How to Create a Facebook Application for Vanillaforums Single Sign-On (SSO)</a>.</p>
   </div>
</div>
<?php
   echo $this->Form->Close();
