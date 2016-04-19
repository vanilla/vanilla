<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::session();

?>
<style>
  /*Checkerboard background for transparent images  */
  img {
    background-color: #FEFEFE;
    background-image: -webkit-linear-gradient(45deg, #d8d8d8 25%, transparent 25%, transparent 75%, #d8d8d8 75%, #d8d8d8), -webkit-linear-gradient(45deg, #d8d8d8 25%, transparent 25%, transparent 75%, #d8d8d8 75%, #d8d8d8);
    background-image: -moz-linear-gradient(45deg, #d8d8d8 25%, transparent 25%, transparent 75%, #d8d8d8 75%, #d8d8d8), -moz-linear-gradient(45deg, #d8d8d8 25%, transparent 25%, transparent 75%, #d8d8d8 75%, #d8d8d8);
    background-image: -o-linear-gradient(45deg, #d8d8d8 25%, transparent 25%, transparent 75%, #d8d8d8 75%, #d8d8d8), -o-linear-gradient(45deg, #d8d8d8 25%, transparent 25%, transparent 75%, #d8d8d8 75%, #d8d8d8);
    background-image: -ms-linear-gradient(45deg, #d8d8d8 25%, transparent 25%, transparent 75%, #d8d8d8 75%, #d8d8d8), -ms-linear-gradient(45deg, #d8d8d8 25%, transparent 25%, transparent 75%, #d8d8d8 75%, #d8d8d8);
    background-image: linear-gradient(45deg, #d8d8d8 25%, transparent 25%, transparent 75%, #d8d8d8 75%, #d8d8d8), linear-gradient(45deg, #d8d8d8 25%, transparent 25%, transparent 75%, #d8d8d8 75%, #d8d8d8);
    -webkit-background-size: 8px 8px;
    -moz-background-size: 8px 8px;
    background-size: 8px 8px;
    background-position: 0 0, 4px 4px;
  }
</style>
<h1><?php echo t('Email Styles'); ?></h1>
<?php echo wrap(anchor(t('Send a Test Email'), '/dashboard/settings/emailtest', 'Popup Button', array('style' => 'margin-top:20px')), 'div'); ?>
<div class="Info">
    <?php
    echo '<h2>'.t('HTML Emails').'</h2>'; ?>
    <div class="Info" style="margin-left: 0; padding-left: 0;">
        <?php
        echo t('Spruce up your emails by adding a logo and customizing the colors.');
        echo '<br>'.t('You can send emails in plain text by disabling the toggle below.');
        ?>
    </div>
    <span id="plaintext-toggle">
        <?php
        if (strtolower(c('Garden.Email.Format', 'text') === 'text')) {
            echo wrap(anchor(t('Disabled'), '/dashboard/settings/setemailformat/html', 'Hijack SmallButton', array('onclick' => 'emailStyles.showSettings();')), 'span', array('class' => "ActivateSlider ActivateSlider-Inactive"));
        } else {
            echo wrap(anchor(t('Enabled'), '/dashboard/settings/setemailformat/text', 'Hijack SmallButton', array('onclick' => 'emailStyles.hideSettings();')), 'span', array('class' => "ActivateSlider ActivateSlider-Active"));
        }
        ?>
    </span>
</div>
<div class="html-email-settings js-html-email-settings">
    <?php
    echo $this->Form->open(array('enctype' => 'multipart/form-data'));
    echo $this->Form->errors();
    $emailImage = c('Garden.EmailTemplate.Image');
    ?>
    <div class="Padded email-image">
        <?php
        if ($this->data('EmailImage')) {
            echo img($this->data('EmailImage'), array('class' => 'js-email-image'));
        } else {
            echo '<img class="js-email-image Hidden"/>';
        }
        ?>
    </div>
    <?php
    echo wrap(anchor(t('Upload New Email Logo'), '/dashboard/settings/emailimage', 'js-upload-email-image-button Button'), 'div', array('style' => 'margin-bottom: 20px'));
    ?>
    <?php
    $hideCssClass = $emailImage ? '' : ' Hidden';
    echo wrap(t('Remove Email Logo'), 'div', array('class' => 'js-remove-email-image-button RemoveButton Button'.$hideCssClass));
    ?>
    <ul>
        <li>
            <?php
            echo $this->Form->label('Text Color', 'Garden.EmailTemplate.TextColor');
            echo $this->Form->color('Garden.EmailTemplate.TextColor', 'text-color');
            ?>
        </li>
        <li>
            <?php
            echo $this->Form->label('Background Color', 'Garden.EmailTemplate.BackgroundColor');
            echo $this->Form->color('Garden.EmailTemplate.BackgroundColor', 'background-color');
            ?>
        </li>
        <li>
            <?php
            echo $this->Form->label('Page Color', 'Garden.EmailTemplate.ContainerBackgroundColor');
            echo $this->Form->color('Garden.EmailTemplate.ContainerBackgroundColor', 'container-background-color');
            ?>
        </li>
        <li>
            <?php
            echo $this->Form->label('Button Background Color', 'Garden.EmailTemplate.ButtonBackgroundColor');
            echo $this->Form->color('Garden.EmailTemplate.ButtonBackgroundColor', 'button-background-color');
            ?>
        </li>
        <li>
            <?php
            echo $this->Form->label('Button Text Color', 'Garden.EmailTemplate.ButtonTextColor');
            echo $this->Form->color('Garden.EmailTemplate.ButtonTextColor', 'button-text-color');
            ?>
        </li>
    </ul>
    <?php echo wrap(t('Preview Colors'), 'span', array('class' => 'js-email-preview-button Button', 'style' => 'line-height: 1.25;'));
    echo $this->Form->button(t('Save Colors')); ?>
</div>
