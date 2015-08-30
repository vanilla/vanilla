<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::session();

?>
    <h1><?php echo t('Outgoing Email'); ?></h1>
<?php
echo $this->Form->open(array('autocomplete' => 'off'));
echo $this->Form->errors();
?>
    <ul>
        <li>
            <div
                class="Info"><?php echo t("Email sent from the application will be addressed from the following name and address"); ?></div>
        </li>
        <li>
            <?php
            echo $this->Form->label('Name', 'Garden.Email.SupportName');
            echo $this->Form->textBox('Garden.Email.SupportName');
            ?>
        </li>
        <li>
            <?php
            echo $this->Form->label('Email', 'Garden.Email.SupportAddress');
            echo $this->Form->textBox('Garden.Email.SupportAddress');
            ?>
        </li>
        <li>
            <div
                class="Info"><?php echo t('We will attempt to use the local mail server to send email by default. If you want to use a separate SMTP mail server, you can configure it below.'); ?></div>
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
            echo $this->Form->label('SMTP Host', 'Garden.Email.SmtpHost');
            echo $this->Form->textBox('Garden.Email.SmtpHost');
            ?>
        </li>
        <li>
            <?php
            echo $this->Form->label('SMTP User', 'Garden.Email.SmtpUser');
            echo $this->Form->textBox('Garden.Email.SmtpUser', array('autocomplete' => 'off'));
            ?>
        </li>
        <li>
            <?php
            echo $this->Form->label('SMTP Password', 'Garden.Email.SmtpPassword');
            echo $this->Form->Input('Garden.Email.SmtpPassword', 'password', array('autocomplete' => 'off'));
            ?>
        </li>
        <li>
            <?php
            echo $this->Form->label('SMTP Port', 'Garden.Email.SmtpPort');
            echo $this->Form->textBox('Garden.Email.SmtpPort', array('class' => 'SmallInput'));
            ?>
        </li>
        <li>
            <?php
            echo $this->Form->label('SMTP Security', 'Garden.Email.SmtpSecurity');
            echo $this->Form->RadioList('Garden.Email.SmtpSecurity', array('' => 'None', 'ssl' => 'SSL', 'tls' => 'TLS'), array());
            ?>
        </li>
    </ul>
<?php echo $this->Form->close('Save');
