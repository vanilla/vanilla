<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo t('Default Avatar'); ?></h1>
<?php
echo $this->Form->open(array('enctype' => 'multipart/form-data'));
echo $this->Form->errors();
$defaultAvatar = c('Garden.DefaultAvatar');
?>
<?php
if ($this->data('crop')) {
    echo $this->data('crop');
} else { ?>
    <div class="avatars">
        <div class="Padded current-avatar">
            <?php echo img($this->data('avatar'), array('style' => 'width: '.c('Garden.Thumbnail.Size').'px; height: '.c('Garden.Thumbnail.Size').'px;')); ?>
        </div>
    </div>
<?php } ?>
<div class="js-new-avatar Button" style="margin-bottom: 20px;">Upload New Avatar</div>
<?php
echo $this->Form->input('DefaultAvatar', 'file', array('class' => 'js-new-avatar-upload Hidden'));
if ($defaultAvatar) {
    echo wrap(anchor(t('Remove Default Avatar'), '/dashboard/settings/removedefaultavatar/'.Gdn::session()->transientKey(), 'Button'), 'div');
}
?>
<?php echo $this->Form->close(); ?>

