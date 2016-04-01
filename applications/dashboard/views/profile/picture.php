<?php if (!defined('APPLICATION')) exit();
// Check that we have the necessary tools to allow image uploading
$allowImages = Gdn_UploadImage::CanUploadImages();

echo $this->Form->open(array('enctype' => 'multipart/form-data', 'class' => 'js-change-picture-form'));
echo $this->Form->errors();
// Is the photo hosted remotely?
$remotePhoto = isUrl($this->User->Photo);
if ($this->data('crop') && $allowImages) {
    echo $this->data('crop');
} else { ?>
    <div class="avatars">
        <div class="Padded current-avatar">
            <?php echo img($this->data('avatar'), array('style' => 'width: '.c('Garden.Thumbnail.Size').'px; height: '.c('Garden.Thumbnail.Size').'px;')); ?>
        </div>
    </div>
<?php } ?>
<div class="js-new-avatar Button" style="margin-bottom: 20px;"><?php echo t('Upload New Picture'); ?></div>
<?php
echo $this->Form->input('Avatar', 'file', array('class' => 'js-new-avatar-upload Hidden'));
if ($this->data('crop')) {
    echo wrap(anchor(t('Remove Picture'), userUrl($this->User, '', 'removepicture').'?tk='.Gdn::session()->TransientKey(), 'Button Danger PopConfirm'), 'div');
}
?>
<?php echo $this->Form->close(); ?>
