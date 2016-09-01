<?php if (!defined('APPLICATION')) exit(); ?>
    <h1><?php echo t('Email Logo'); ?></h1>
<?php
echo $this->Form->open(array('enctype' => 'multipart/form-data', 'class' => 'js-email-image-form'));
echo $this->Form->errors(); ?>
<div class="form-group row">
    <div class="label-wrap">
    <?php
    echo t('Choose a new email logo'); ?>
        <div class="info">
            <?php
                echo sprintf(t('Large images will be scaled down.'),
                c('Garden.EmailTemplate.ImageMaxWidth', 400),
                c('Garden.EmailTemplate.ImageMaxHeight', 300));
            ?>
        </div>
    </div>
    <div class="input-wrap">
        <?php
//        $this->Form = new Gdn_Form();
        echo $this->Form->fileUpload('EmailImage', ['class' => 'js-new-image-upload', 'onchange' => 'emailStyles.submitImageForm()']);
        echo $this->Form->close(''); ?>
    </div>
</div>
