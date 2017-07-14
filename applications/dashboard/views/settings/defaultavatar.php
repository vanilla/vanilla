<?php if (!defined('APPLICATION')) exit(); ?>
<?php
echo heading(t('Default Avatar'), t('Upload New Avatar'), '', 'js-new-avatar btn btn-primary', '/dashboard/settings/avatars');
echo $this->Form->open(['enctype' => 'multipart/form-data']);
echo $this->Form->errors();
echo $this->Form->input('DefaultAvatar', 'file', ['class' => 'js-new-avatar-upload hidden']);
if ($this->data('crop')) {
    echo $this->data('crop');
} else { ?>
    <div class="avatars">
        <div class="padded current-avatar">
            <?php echo img($this->data('avatar'), ['style' => 'width: '.c('Garden.Thumbnail.Size').'px; height: '.c('Garden.Thumbnail.Size').'px;']); ?>
        </div>
    </div>
<?php } ?>
<?php if ($this->data('crop')) { ?>
<div class="form-footer js-modal-footer">
    <?php echo anchor('Cancel', '/dashboard/settings/avatars', '', ['class' => 'btn btn-secondary']); ?>
    <?php echo $this->Form->button('Save', ['class' => 'js-save-avatar-crop btn btn-primary']); ?>
</div>
<?php } ?>
<?php echo $this->Form->close(); ?>

