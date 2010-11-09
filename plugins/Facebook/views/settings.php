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
   width: 120px;  
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
      </ul>
      <?php echo $this->Form->Button('Save', array('class' => 'Button SliceSubmit')); ?>
   </div>
   <div class="ConfigurationHelp">
      <strong>How to set up Facebook Connect</strong>
      <p>In order to set up Facebook Connect, you must create an "application" in Facebook at: <a href="http://www.facebook.com/developers/apps.php">http://www.facebook.com/developers/apps.php</a></p>
      <p>
         When you create the Facebook application, you can choose what to enter in most fields, but make sure you enter the following value in the "Site Url" field:
         <input type="text" class="CopyInput" value="<?php echo rtrim(Gdn::Request()->Domain(), '/').'/'; ?>" />
      </p>
      <p><?php echo Anchor(Img('/plugins/Facebook/design/help-siteurl.png', array('style' => 'max-width: 940px;')), '/plugins/Facebook/design/help-siteurl.png', array('target' => '_blank')); ?></p>
      <p>Once your application has been set up, you must copy the "Application ID" and "Application Secret" into the form on this page and click save.</p>
      <p><?php echo Anchor(Img('/plugins/Facebook/design/help-appvalues.png', array('style' => 'max-width: 746px;')), '/plugins/Facebook/design/help-appvalues.png', array('target' => '_blank')); ?></p>
   </div>
</div>
<?php 
   echo $this->Form->Close();
