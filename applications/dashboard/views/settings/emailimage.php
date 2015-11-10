<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::session();
?>
    <h1><?php echo t('Email Banner'); ?></h1>
<?php
echo $this->Form->open(array('enctype' => 'multipart/form-data', 'class' => 'js-email-image-form'));
echo $this->Form->errors();
echo $this->Form->input('EmailImage', 'file', array('class' => 'js-new-image-upload', 'onchange' => 'emailStyles.submitImageForm()'));
echo $this->Form->close('');
