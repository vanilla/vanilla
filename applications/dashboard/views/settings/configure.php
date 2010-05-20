<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();

?>
<h1><?php echo T('General Settings'); ?></h1>
<?php
echo $this->Form->Open(array('enctype' => 'multipart/form-data'));
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
         echo $this->Form->Label('Banner Logo', 'Garden.Logo');
         $Logo = C('Garden.Logo');
         if ($Logo) {
            echo Wrap(
               Img($Logo),
               'div'
            );
            echo Wrap(Anchor('Remove Banner Logo', '/dashboard/settings/removelogo/'.$Session->TransientKey(), 'SmallButton'), 'div', array('style' => 'padding: 10px 0;'));
            echo Wrap(
               T('Browse for a new banner logo if you would like to change it:'),
               'div',
               array('class' => 'Info')
            );
         } else {
            echo Wrap(
               T('The banner logo appears at the top of your forum.'),
               'div',
               array('class' => 'Info')
            );
         }
         
         echo $this->Form->Input('Logo', 'file');
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->Label('Banner Title', 'Garden.Title');
         echo $this->Form->TextBox('Garden.Title');
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->CheckBox('Garden.RewriteUrls', "Use Garden's .htaccess file to rewrite urls");
         
         if(!$this->Data['HasModRewrite']) {
            echo '<div class="Warning">',
               T('Garden.NoModRewrite',
               'The server configuration for this setting could not be found. If you enable this setting your site may become unavailable.'),
               '</div>';
         }
      ?>
   </li>
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
</ul>
<?php echo $this->Form->Close('Save');
