<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::session();

?>
<h1><?php echo t('Email Styles'); ?></h1>
<div class="Info">
    <?php
    echo '<h2>'.t('HTML Emails').'</h2>'; ?>
    <div class="Info" style="margin-left: 0; padding-left: 0;">
	<?php
	echo t('Spruce up your emails by adding a banner image and customizing the colors.');
	echo '<br>'.t('You can also send emails in plain text by disabling the toggle below.');
	?>
    </div>
    <span id="plaintext-toggle">
	<?php
	if (!c('Garden.Email.Styles.Plaintext')) {
	    echo wrap(anchor(t('Enabled'), '/dashboard/settings/setPlaintextEmail/1', 'Hijack SmallButton', array('onclick' => 'emailStyles.hideSettings()')), 'span', array('class' => "ActivateSlider ActivateSlider-Active"));
	} else {
	    echo wrap(anchor(t('Disabled'), '/dashboard/settings/setPlaintextEmail/0', 'Hijack SmallButton', array('onclick' => 'emailStyles.showSettings()')), 'span', array('class' => "ActivateSlider ActivateSlider-Inactive"));
	}
	?>
    </span>
</div>
<div class="html-email-settings js-html-email-settings">
    <?php
    echo $this->Form->open(array('enctype' => 'multipart/form-data'));
    echo $this->Form->errors();

    $emailImage = c('Garden.Email.Styles.Image');
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
    echo wrap(anchor(t('Upload New Email Banner'), '/dashboard/settings/emailimage/'.Gdn::session()->transientKey(), 'UploadImage Button'), 'div', array('style' => 'margin-bottom: 20px'));
    ?>
    <?php
    $hideCssClass = $emailImage ? '' : ' Hidden';
    echo wrap(t('Remove Email Banner'), 'div', array('class' => 'js-remove-email-image-button RemoveButton Button'.$hideCssClass));
    ?>
    <ul>
	<li>
	    <?php
	    echo $this->Form->label('Background Color', 'Garden.Email.Styles.BackgroundColor');
	    echo $this->Form->color('Garden.Email.Styles.BackgroundColor', 'background-color');
	    ?>
	</li>
	<li>
	    <?php
	    echo $this->Form->label('Button Background Color', 'Garden.Email.Styles.ButtonBackgroundColor');
	    echo $this->Form->color('Garden.Email.Styles.ButtonBackgroundColor', 'button-background-color');
	    ?>
	</li>
	<li>
	    <?php
	    echo $this->Form->label('Link Color', 'Garden.Email.Styles.LinkColor');
	    echo $this->Form->color('Garden.Email.Styles.LinkColor', 'link-color');
	    ?>
	</li>
    </ul>
    <?php echo $this->Form->button(t('Save Colors')); ?>
</div>
