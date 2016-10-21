<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::session();
echo heading(t('Email Styles'), t('Send a Test Email'), '/dashboard/settings/emailtest', 'js-modal btn btn-primary');
?>
<div class="row form-group">
    <div class="label-wrap-wide">
        <?php echo t('Enable HTML emails'); ?>
        <div class="info"><?php echo t('Spruce up your emails by adding a logo and customizing the colors.'); ?></div>
    </div>
    <div class="input-wrap-right">
        <span id="plaintext-toggle">
            <?php
            if (strtolower(c('Garden.Email.Format', 'text') === 'text')) {
                echo wrap(anchor('<div class="toggle-well"></div><div class="toggle-slider"></div>', '/dashboard/settings/setemailformat/html', 'Hijack', array('onclick' => 'emailStyles.showSettings();')), 'span', array('class' => "toggle-wrap toggle-wrap-off"));
            } else {
                echo wrap(anchor('<div class="toggle-well"></div><div class="toggle-slider"></div>', '/dashboard/settings/setemailformat/text', 'Hijack', array('onclick' => 'emailStyles.hideSettings();')), 'span', array('class' => "toggle-wrap toggle-wrap-on"));
            }
            ?>
        </span>
    </div>
</div>
<div class="html-email-settings js-html-email-settings">
    <?php
    echo $this->Form->open(array('enctype' => 'multipart/form-data'));
    echo $this->Form->errors();
    ?>
    <ul>
        <?php echo $this->Form->imageUploadPreview(
            'EmailImage',
            t('Email Logo'),
            sprintf(t('Large images will be scaled down.'),
                    c('Garden.EmailTemplate.ImageMaxWidth', 400),
                    c('Garden.EmailTemplate.ImageMaxHeight', 300)),
            $this->data('EmailImage'),
            '/dashboard/settings/removeemailimage',
            t('Remove Email Logo'),
            t('Are you sure you want to delete your email logo?')
        ); ?>
        <li class="row form-group">
            <div class="label-wrap"><?php echo $this->Form->label('Text Color', 'Garden.EmailTemplate.TextColor'); ?></div>
            <div class="input-wrap"><?php echo $this->Form->color('Garden.EmailTemplate.TextColor', 'text-color'); ?></div>
        </li>
        <li class="row form-group">
            <div class="label-wrap"><?php echo $this->Form->label('Background Color', 'Garden.EmailTemplate.BackgroundColor'); ?></div>
            <div class="input-wrap"><?php echo $this->Form->color('Garden.EmailTemplate.BackgroundColor', 'background-color'); ?></div>
        </li>
        <li class="row form-group">
            <div class="label-wrap"><?php echo $this->Form->label('Page Color', 'Garden.EmailTemplate.ContainerBackgroundColor'); ?></div>
            <div class="input-wrap"><?php echo $this->Form->color('Garden.EmailTemplate.ContainerBackgroundColor', 'container-background-color'); ?></div>
        </li>
        <li class="row form-group">
            <div class="label-wrap"><?php echo $this->Form->label('Button Background Color', 'Garden.EmailTemplate.ButtonBackgroundColor'); ?></div>
            <div class="input-wrap"><?php echo $this->Form->color('Garden.EmailTemplate.ButtonBackgroundColor', 'button-background-color'); ?></div>
        </li>
        <li class="row form-group">
            <div class="label-wrap"><?php echo $this->Form->label('Button Text Color', 'Garden.EmailTemplate.ButtonTextColor'); ?></div>
            <div class="input-wrap"><?php echo $this->Form->color('Garden.EmailTemplate.ButtonTextColor', 'button-text-color'); ?></div>
        </li>
    </ul>
    <div class="buttons form-footer">
        <?php echo wrap(t('Preview'), 'span', array('class' => 'js-email-preview-button btn btn-secondary')); ?>
        <?php echo $this->Form->button(t('Save')); ?>
    </div>
</div>
<?php echo $this->Form->close(); ?>
