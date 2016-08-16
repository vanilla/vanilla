<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::session();
?>
<div class="header-block">
<h1><?php echo t('Email Styles'); ?></h1>
<?php echo wrap(anchor(t('Send a Test Email'), '/dashboard/settings/emailtest', 'Popup btn-primary btn'), 'div'); ?>
</div>
<div class="row form-group">
    <div class="label-wrap-wide">
        <?php echo t('Enable HTML emails'); ?>
        <div class="info"><?php echo t('Spruce up your emails by adding a logo and customizing the colors.'); ?></div>
    </div>
    <div class="input-wrap-right">
        <span id="plaintext-toggle">
            <?php
            if (strtolower(c('Garden.Email.Format', 'text') === 'text')) {
                echo wrap(anchor('<div class="toggle-well"></div><div class="toggle-slider"></div>', '/dashboard/settings/setemailformat/html', 'Hijack', array('onclick' => 'emailStyles.showSettings();')), 'span', array('class' => "toggle-wrap toggle-wrap-off ActivateSlider ActivateSlider-Inactive"));
            } else {
                echo wrap(anchor('<div class="toggle-well"></div><div class="toggle-slider"></div>', '/dashboard/settings/setemailformat/text', 'Hijack', array('onclick' => 'emailStyles.hideSettings();')), 'span', array('class' => "toggle-wrap toggle-wrap-on ActivateSlider ActivateSlider-Active"));
            }
            ?>
        </span>
    </div>
</div>
<div class="html-email-settings js-html-email-settings">
    <?php
    echo $this->Form->open(array('enctype' => 'multipart/form-data'));
    echo $this->Form->errors();
    $emailImage = c('Garden.EmailTemplate.Image');
    ?>
    <div class="email-image">
        <?php
        if ($this->data('EmailImage')) {
            echo img($this->data('EmailImage'), array('class' => 'js-email-image padded-top'));
        } else {
            echo '<img class="js-email-image Hidden"/>';
        }
        ?>
    </div>
    <div class="buttons padded">
        <?php
        echo anchor(t('Upload New Email Logo'), '/dashboard/settings/emailimage', 'js-upload-email-image-button btn btn-primary');
        ?>
        <?php
        $hideCssClass = $emailImage ? '' : ' Hidden';
        echo anchor(t('Remove Email Logo'), '/dashboard/settings/removeemailimage', 'js-modal-confirm js-hijack btn btn-primary '.$hideCssClass);
    ?>
    </div>
    <ul>
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
        <?php echo wrap(t('Preview Colors'), 'span', array('class' => 'js-email-preview-button btn btn-secondary')); ?>
        <?php echo $this->Form->button(t('Save Colors')); ?>
    </div>
</div>
<?php echo $this->Form->close(); ?>
