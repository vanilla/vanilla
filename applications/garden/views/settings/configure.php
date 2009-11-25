<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo Gdn::Translate('General Settings'); ?></h1>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<ul>
   <li>
      <?php
         echo $this->Form->Label('Language', 'Garden.Locale');
         echo $this->Form->DropDown('Garden.Locale', $this->LocaleData, array('TextField' => 'Code', 'ValueField' => 'Code'));
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->Label('Application Title', 'Garden.Title');
         echo $this->Form->TextBox('Garden.Title');
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->CheckBox('Garden.RewriteUrls', "Use Garden's .htaccess file to rewrite urls");
      ?>
   </li>
   <li>
      <div class="Info"><?php echo Translate("Email sent from Garden will be addressed from the following name and address"); ?></div>
   </li>
   <li>
      <?php
         echo $this->Form->Label('Name', 'Garden.Email.SupportName');
         echo $this->Form->TextBox('Garden.Email.SupportName');
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->Label('Email', 'Garden.Email.SupportAddress');
         echo $this->Form->TextBox('Garden.Email.SupportAddress');
      ?>
   </li>
   <li>
      <div class="Info"><?php echo Gdn::Translate('Garden will attempt to use the local mail server to send email by default. If you want to use a separate SMTP mail server, you can configure it below.'); ?></div>
   </li>
   <li>
      <?php
         echo $this->Form->CheckBox('Garden.Email.UseSmtp', "Use an SMTP server to send email");
      ?>
   </li>
</ul>
<ul id="SmtpOptions">
   <li>
      <?php
         echo $this->Form->Label('SMTP Host', 'Garden.Email.SmtpHost');
         echo $this->Form->TextBox('Garden.Email.SmtpHost');
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->Label('SMTP User', 'Garden.Email.SmtpUser');
         echo $this->Form->TextBox('Garden.Email.SmtpUser');
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->Label('SMTP Password', 'Garden.Email.SmtpPassword');
         echo $this->Form->Input('Garden.Email.SmtpPassword','password');
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->Label('SMTP Port', 'Garden.Email.SmtpPort');
         echo $this->Form->TextBox('Garden.Email.SmtpPort', array('class' => 'SmallInput'));
      ?>
   </li>
</ul>
<?php echo $this->Form->Close('Save');
