<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo t('Avatars'); ?></h1>
<?php
echo wrap(t('Manage your avatar settings.'), 'div', array('class' => 'Info'));
?>
<h1><?php echo t('Default Avatar'); ?></h1>
<div class="avatars">
    <div class="Padded current-avatar">
        <?php echo img($this->data('avatar'), array('style' => 'width: '.c('Garden.Thumbnail.Size').'px; height: '.c('Garden.Thumbnail.Size').'px;')); ?>
    </div>
    <div class="change-avatar">
        <?php echo anchor('Change', '/dashboard/settings/defaultavatar', 'Button Primary'); ?>
    </div>
    <div class="Padded more-block">
        <a href="" class="more-link js-more-link <?php echo $this->data('moreHidden'); ?>">Show advanced settings</a>
        <a href="" class="less-link js-less-link <?php echo $this->data('lessHidden'); ?>">Hide advanced settings</a>
    </div>
</div>
<div class="js-avatars-advanced-settings <?php echo $this->data('lessHidden'); ?>">
    <?php echo $this->Form->open(array('enctype' => 'multipart/form-data')); ?>
    <h1><?php echo t('Avatar sizes'); ?></h1>
    <?php echo wrap(t('Change the sizes that avatar images are saved at.').' '.t('Changes will apply to newly uploaded avatars only.'), 'div', array('class' => 'Info'));
    echo $this->Form->errors(); ?>
    <ul>
        <li>
            <?php
            echo $this->Form->label('Thumbnail Size', 'Garden.Thumbnail.Size');
            echo wrap(t('Avatars will have their thumbnails saved at this size.'), 'div', array('class' => 'Info'));
            echo $this->Form->textBox('Garden.Thumbnail.Size', array('class' => 'SmallInput')).' px';
            ?>
        </li>
        <li>
            <?php
            echo $this->Form->label('Max Avatar Width', 'Garden.Profile.MaxWidth');
            echo wrap(t('Avatars will be scaled down if they exceed this width.'), 'div', array('class' => 'Info'));
            echo $this->Form->textBox('Garden.Profile.MaxWidth', array('class' => 'SmallInput')).' px';
            ?>
        </li>
        <li>
            <?php
            echo $this->Form->label('Max Avatar Height', 'Garden.Profile.MaxHeight');
            echo wrap(t('Avatars will be scaled down if they exceed this height.'), 'div', array('class' => 'Info'));
            echo $this->Form->textBox('Garden.Profile.MaxHeight', array('class' => 'SmallInput')).' px';
            ?>
        </li>
    </ul>
    <div>
        <?php echo $this->Form->close('Save', '', array('class' => 'Button Primary', 'style' => 'margin-top: 20px;')); ?>
    </div>
</div>
