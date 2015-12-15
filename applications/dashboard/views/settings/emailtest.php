<?php if (!defined('APPLICATION')) exit(); ?>
    <h1><?php echo t('Send a Test Email'); ?></h1>
<div class="Padded">
<?php
echo $this->Form->open(array('enctype' => 'multipart/form-data'));
echo $this->Form->errors();
echo '<p>'.t('TextEnterEmails', 'Type email addresses separated by commas here').'</p>';
echo $this->Form->input('EmailTestAddresses', 'text', array('class' => 'InputBox BigInput'));
echo $this->Form->close(t('Send'), '', array('style' => 'margin-left: 0;margin-top:10px;')); ?>
</div>
