<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::session();

?>
    <h1><?php echo t('Outgoing Email'); ?></h1>
<?php
echo $this->Form->open(['autocomplete' => 'off']);
echo $this->Form->errors();
?>
    <ul>
        <li class="form-group">
            <div class="label-wrap">
                <?php echo $this->Form->label('Name', 'Garden.Email.SupportName'); ?>
                <div class="info"><?php echo t("Email sent from the application will be addressed from the following name."); ?></div>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->textBox('Garden.Email.SupportName'); ?>
            </div>
        </li>
        <li class="form-group">
            <div class="label-wrap">
                <?php echo $this->Form->label('Email', 'Garden.Email.SupportAddress'); ?>
                <div class="info"><?php echo t("Email sent from the application will be addressed from the following address."); ?></div>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->textBox('Garden.Email.SupportAddress'); ?>
            </div>
        </li>
        <li class="form-group">
            <div class="label-wrap">
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->checkBox('Garden.Email.OmitToName', 'Do not include usernames in the "to" field of outgoing e-mails.'); ?>
            </div>
        </li>
        <li class="form-group">
            <div class="label-wrap">
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->checkBox('Garden.Email.UseSmtp', "Use an SMTP server to send email"); ?>
                <div class="info"><?php echo t('We will attempt to use the local mail server to send email by default. If you want to use a separate SMTP mail server, you can configure it below.'); ?></div>
            </div>
        </li>
    </ul>
    <ul id="SmtpOptions">
        <li class="form-group">
            <div class="label-wrap">
                <?php echo $this->Form->label('SMTP Host', 'Garden.Email.SmtpHost'); ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->textBox('Garden.Email.SmtpHost'); ?>
            </div>
        </li>
        <li class="form-group">
            <div class="label-wrap">
                <?php echo $this->Form->label('SMTP User', 'Garden.Email.SmtpUser'); ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->textBox('Garden.Email.SmtpUser', ['autocomplete' => 'off']); ?>
            </div>
        </li>
        <li class="form-group">
            <div class="label-wrap">
                <?php echo $this->Form->label('SMTP Password', 'Garden.Email.SmtpPassword'); ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->input('Garden.Email.SmtpPassword', 'password', ['autocomplete' => 'off']); ?>
            </div>
        </li>
        <li class="form-group">
            <div class="label-wrap">
                <?php echo $this->Form->label('SMTP Port', 'Garden.Email.SmtpPort'); ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->textBox('Garden.Email.SmtpPort'); ?>
            </div>
        </li>
        <li class="form-group">
            <div class="label-wrap">
                <?php echo $this->Form->label('SMTP Security', 'Garden.Email.SmtpSecurity'); ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->radioList('Garden.Email.SmtpSecurity', ['' => 'None', 'ssl' => 'SSL', 'tls' => 'TLS']); ?>
            </div>
        </li>
    </ul>
<?php echo $this->Form->close('Save'); ?>
