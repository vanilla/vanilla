<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo t('Avatars'); ?></h1>
<div class="avatar-default">
    <div class="avatars padded flex">
        <div class="current-avatar flex">
            <div class="image-wrap">
                <?php echo img($this->data('avatar'), array('style' => 'width: '.c('Garden.Thumbnail.Size').'px; height: '.c('Garden.Thumbnail.Size').'px;')); ?>
            </div>
            <div class="default-avatar-label-wrap padded-left">
                <div class="label"><?php echo t('Default Avatar'); ?></div>
                <?php echo t('This is the avatar that will be used if the user doesn’t upload their own avatar image.'); ?>
            </div>
        </div>
        <?php
        $disabled = '';
        if (!c('Garden.DefaultAvatar')) {
            $disabled = 'disabled ';
        }
        ?>
        <div class="options padded-left flex">
            <?php echo anchor(t('Remove'), '/dashboard/settings/removedefaultavatar/', $disabled.'btn btn-primary js-hijack js-modal-confirm remove-avatar'); ?>
            <?php echo anchor(t('Change'), '/dashboard/settings/defaultavatar', 'btn btn-primary change-avatar'); ?>
        </div>
    </div>
</div>
<div class="avatar-advanced">
    <div class="subheading-block">
        <div class="subheading"><?php echo t('Avatar Settings'); ?></div>
        <div><?php echo t('Change the sizes that avatar images are saved at.').' '.t('Changes will apply to newly uploaded avatars only.'); ?></div>
    </div>
    <?php echo $this->Form->open(array('enctype' => 'multipart/form-data')); ?>
    <?php echo $this->Form->errors(); ?>
    <ul>
        <li class="form-group row">
            <div class="label-wrap-wide">
                <?php
                echo $this->Form->label('Thumbnail Size', 'Garden.Thumbnail.Size');
                echo wrap(t('Avatars will have their thumbnails saved at this size.'), 'div', array('class' => 'info')); ?>
            </div>
            <div class="input-wrap-right">
                <div class="textbox-suffix" data-suffix="<?php echo t('px'); ?>">
                    <?php echo $this->Form->textBox('Garden.Thumbnail.Size'); ?>
                </div>
            </div>
        </li>
        <li class="form-group row">
            <div class="label-wrap-wide">
                <?php
                echo $this->Form->label('Max Avatar Width', 'Garden.Profile.MaxWidth');
                echo wrap(t('Avatars will be scaled down if they exceed this width.'), 'div', array('class' => 'info')); ?>
            </div>
            <div class="input-wrap-right">
                <div class="textbox-suffix" data-suffix="<?php echo t('px'); ?>">
                    <?php echo $this->Form->textBox('Garden.Profile.MaxWidth'); ?>
                </div>
            </div>
        </li>
        <li class="form-group row">
            <div class="label-wrap-wide">
                <?php
                echo $this->Form->label('Max Avatar Height', 'Garden.Profile.MaxHeight');
                echo wrap(t('Avatars will be scaled down if they exceed this height.'), 'div', array('class' => 'info')); ?>
            </div>
            <div class="input-wrap-right">
                <div class="textbox-suffix" data-suffix="<?php echo t('px'); ?>">
                    <?php echo $this->Form->textBox('Garden.Profile.MaxHeight'); ?>
                </div>
            </div>
        </li>
    </ul>
    <div class="form-footer">
        <?php echo $this->Form->close('Save', '', array('class' => 'Button Primary')); ?>
    </div>
</div>
