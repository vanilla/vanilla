<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();

?>
<h1><?php echo T('Outgoing Email'); ?></h1>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<ul>
   <li>
      <div class="Info"><?php echo T("Email sent from the application will be addressed from the following name and address"); ?></div>
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
      <div class="Info"><?php echo T('We will attempt to use the local mail server to send email by default. If you want to use a separate SMTP mail server, you can configure it below.'); ?></div>
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
   <li>
      <?php
         echo $this->Form->Label('SMTP Security', 'Garden.Email.SmtpSecurity');
         echo $this->Form->RadioList('Garden.Email.SmtpSecurity', array('' => 'None', 'ssl' => 'SSL', 'tls' => 'TLS'), array());
      ?>
   </li>
</ul>
<?php echo $this->Form->Close('Save');
