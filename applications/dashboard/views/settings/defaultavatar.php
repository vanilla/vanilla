<?php if (!defined('APPLICATION')) exit(); ?>
<?php
echo $this->Form->open(array('enctype' => 'multipart/form-data'));
echo $this->Form->errors();
?>
<div class="header-block">
    <div class="title-block">
        <?php echo anchor(dashboardSymbol('chevron-left'), "/dashboard/settings/avatars", 'btn btn-icon btn-return', ['aria-label' => t('Return')]); ?>
        <h1><?php echo t('Default Avatar'); ?></h1>
    </div>
    <?php echo $this->Form->input('DefaultAvatar', 'file', array('class' => 'js-new-avatar-upload Hidden')); ?>
    <div class="js-new-avatar btn btn-primary"><?php echo t('Upload New Avatar'); ?></div>
</div>
<?php
if ($this->data('crop')) {
    echo $this->data('crop');
} else { ?>
    <div class="avatars">
        <div class="padded current-avatar">
            <?php echo img($this->data('avatar'), array('style' => 'width: '.c('Garden.Thumbnail.Size').'px; height: '.c('Garden.Thumbnail.Size').'px;')); ?>
        </div>
    </div>
<?php } ?>
<?php if ($this->data('crop')) { ?>
<div class="form-footer js-modal-footer">
    <?php echo $this->Form->button('Save', ['class' => 'js-save-avatar-crop btn btn-primary']); ?>
</div>
<?php } ?>
<?php echo $this->Form->close(); ?>

