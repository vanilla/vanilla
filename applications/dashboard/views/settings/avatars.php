<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo t('Avatars'); ?></h1>
    <div class="avatar-default">
    <h2><?php echo t('Default Avatar'); ?></h2>
    <div class="avatars">
        <div class="padded current-avatar">
            <?php echo img($this->data('avatar'), array('style' => 'width: '.c('Garden.Thumbnail.Size').'px; height: '.c('Garden.Thumbnail.Size').'px;')); ?>
        </div>
        <div class="change-avatar">
            <?php echo anchor('Change', '/dashboard/settings/defaultavatar', 'Button Primary'); ?>
        </div>
    </div>
</div>
<div class="avatar-advanced padded">
    <?php echo $this->Form->open(array('enctype' => 'multipart/form-data')); ?>
    <h2><?php echo t('Avatar sizes'); ?></h2>
    <?php echo wrap(t('Change the sizes that avatar images are saved at.').' '.t('Changes will apply to newly uploaded avatars only.'), 'div', array('class' => 'info'));
    echo $this->Form->errors(); ?>
    <ul>
        <li>
            <?php
            echo $this->Form->label('Thumbnail Size', 'Garden.Thumbnail.Size');
            echo wrap(t('Avatars will have their thumbnails saved at this size.'), 'div', array('class' => 'Info'));
            echo $this->Form->textBox('Garden.Thumbnail.Size');
            ?>
        </li>
        <li>
            <?php
            echo $this->Form->label('Max Avatar Width', 'Garden.Profile.MaxWidth');
            echo wrap(t('Avatars will be scaled down if they exceed this width.'), 'div', array('class' => 'Info'));
            echo $this->Form->textBox('Garden.Profile.MaxWidth');
            ?>
        </li>
        <li>
            <?php
            echo $this->Form->label('Max Avatar Height', 'Garden.Profile.MaxHeight');
            echo wrap(t('Avatars will be scaled down if they exceed this height.'), 'div', array('class' => 'Info'));
            echo $this->Form->textBox('Garden.Profile.MaxHeight');
            ?>
        </li>
    </ul>
    <div class="buttons form-footer">
        <?php echo $this->Form->close('Save', '', array('class' => 'Button Primary')); ?>
    </div>
</div>
