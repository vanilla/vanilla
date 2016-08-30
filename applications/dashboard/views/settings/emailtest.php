<?php if (!defined('APPLICATION')) exit(); ?>
    <h1><?php echo t('Send a Test Email'); ?></h1>
<?php
echo $this->Form->open(array('enctype' => 'multipart/form-data'));
echo $this->Form->errors(); ?>
<div class="form-group row">
    <div class="label-wrap">
    <?php echo t('TextEnterEmails', 'Type email addresses separated by commas here'); ?>
    </div>
    <div class="input-wrap">
    <?php echo $this->Form->input('EmailTestAddresses', 'text'); ?>
    </div>
</div>
<div class="form-footer js-modal-footer">
<?php echo $this->Form->close(t('Send')); ?>
</div>
