<?php if (!defined('APPLICATION')) exit(); ?>
    <h1><?php echo t('Email Logo'); ?></h1>
<?php
echo $this->Form->open(array('enctype' => 'multipart/form-data', 'class' => 'js-email-image-form'));
echo $this->Form->errors();
echo '<p>'.t('Choose a new email logo.')
    .sprintf(t('Large images will be scaled down.'),
        c('Garden.EmailTemplate.ImageMaxWidth', 400),
        c('Garden.EmailTemplate.ImageMaxHeight', 300))
    .'</p>';
echo $this->Form->input('EmailImage', 'file', array('class' => 'js-new-image-upload', 'onchange' => 'emailStyles.submitImageForm()'));
echo $this->Form->close('');
