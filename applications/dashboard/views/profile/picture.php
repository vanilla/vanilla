<?php if (!defined('APPLICATION')) exit();
// Check that we have the necessary tools to allow image uploading
$allowImages = Gdn_UploadImage::CanUploadImages();
echo '<div class="change-picture">';
echo '<h2 class="H">'.$this->title().'</h2>';
echo $this->Form->open(array('enctype' => 'multipart/form-data', 'class' => 'js-change-picture-form'));
echo $this->Form->errors();
if ($this->data('crop') && $allowImages) {
    echo $this->data('crop');
} else { ?>
    <div class="avatars">
        <div class="Padded current-avatar">
            <?php echo img($this->data('avatar'), array('style' => 'width: '.c('Garden.Thumbnail.Size').'px; height: '.c('Garden.Thumbnail.Size').'px;')); ?>
        </div>
    </div>
<?php } ?>
<div class="DismissMessage WarningMessage"><?php echo t('By uploading a file you certify that you have the right to distribute this picture and that it does not violate the Terms of Service.'); ?></div>
<div class="js-new-avatar Button" style="margin-bottom: 20px;"><?php echo t('Upload New Picture'); ?></div>
<?php
echo $this->Form->input('Avatar', 'file', array('class' => 'js-new-avatar-upload Hidden'));
if ($this->data('crop')) {
    echo anchor(t('Remove Picture'), userUrl($this->User, '', 'removepicture').'?tk='.Gdn::session()->TransientKey().'&deliveryType='.$this->deliveryType(), 'Button Danger PopConfirm');
}
?>
<?php
echo $this->Form->close();
echo '</div>';
?>
