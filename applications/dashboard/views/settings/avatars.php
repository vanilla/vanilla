<?php if (!defined('APPLICATION')) exit();
/** @var Gdn_Form $form */
$form = $this->Form;
echo heading(t('Avatars')); ?>
<?php foreach ($this->data('messages', []) as $message) : ?>
    <div class="alert alert-info padded">
        <?php echo $message; ?>
    </div>
<?php endforeach;

$permissions = ['Garden.ProfilePicture.Edit', 'Garden.Profiles.Edit'];
$permissions = implode(' or ', $permissions);
$desc = 'Allow users with the '.$permissions.' permission to change their own avatars from their profile pages in Vanilla.';
$allowEditPhotos = c('Garden.Profile.EditPhotos');
?>
<div class="form-group">
    <div class="label-wrap-wide">
        <?php echo '<div class="label">'.t('Allow users to change their own avatars').'</div>'; ?>
        <div class="info"><?php echo $desc; ?></div>
    </div>
    <div class="input-wrap-right">
        <span id="editphotos-toggle">
            <?php
            if ($allowEditPhotos) {
                echo wrap(anchor('<div class="toggle-well"></div><div class="toggle-slider"></div>', '/dashboard/settings/alloweditphotos/true', 'Hijack'), 'span', ['class' => "toggle-wrap toggle-wrap-off"]);
            } else {
                echo wrap(anchor('<div class="toggle-well"></div><div class="toggle-slider"></div>', '/dashboard/settings/alloweditphotos/false', 'Hijack'), 'span', ['class' => "toggle-wrap toggle-wrap-on"]);
            }
            ?>
        </span>
    </div>
</div>
<?php if ($this->data('canSetDefaultAvatar', true)) : ?>
<div class="avatar-default full-border">
    <div class="avatars padded-bottom flex flex-wrap">
        <div class="current-avatar flex padded-top">
            <div class="image-wrap">
                <?php echo img($this->data('avatar'), array('style' => 'width: '.c('Garden.Thumbnail.Size').'px; height: '.c('Garden.Thumbnail.Size').'px;')); ?>
            </div>
            <div class="default-avatar-label-wrap padded-left">
                <div class="label"><?php echo t('Default Avatar'); ?></div>
                <?php echo t('This is the avatar that will be used if the user doesn’t provide their own avatar image.'); ?>
            </div>
        </div>
        <?php
        $disabled = '';
        if (!c('Garden.DefaultAvatar')) {
            $disabled = 'disabled ';
        }
        ?>
        <div class="options padded-top flex">
            <?php echo anchor(t('Remove'), '/dashboard/settings/removedefaultavatar/', $disabled.'btn btn-primary js-hijack js-modal-confirm remove-avatar'); ?>
            <?php echo anchor(t('Change'), '/dashboard/settings/defaultavatar', 'btn btn-primary change-avatar'); ?>
        </div>
    </div>
</div>
<?php endif; ?>
<section>
    <?php
    echo subheading(t('Avatar Dimensions'), t('Change the sizes that avatar images are saved at.').' '.t('Changes will apply to newly uploaded avatars only.'));
    echo $form->open([
        'enctype' => 'multipart/form-data',
        'action' => url('/settings/avatars')
    ]);
    echo $form->errors();
    ?>
    <ul>
        <li class="form-group">
            <div class="label-wrap-wide">
                <?php
                echo $form->label('Thumbnail Size', 'Garden.Thumbnail.Size');
                echo wrap(t('Avatars will have their thumbnails saved at this size.'), 'div', array('class' => 'info')); ?>
            </div>
            <div class="input-wrap-right">
                <div class="textbox-suffix" data-suffix="<?php echo t('px'); ?>">
                    <?php echo $form->textBox('Garden.Thumbnail.Size'); ?>
                </div>
            </div>
        </li>
        <li class="form-group">
            <div class="label-wrap-wide">
                <?php
                echo $form->label('Max Avatar Width', 'Garden.Profile.MaxWidth');
                echo wrap(t('Avatars will be scaled down if they exceed this width.'), 'div', array('class' => 'info')); ?>
            </div>
            <div class="input-wrap-right">
                <div class="textbox-suffix" data-suffix="<?php echo t('px'); ?>">
                    <?php echo $form->textBox('Garden.Profile.MaxWidth'); ?>
                </div>
            </div>
        </li>
        <li class="form-group">
            <div class="label-wrap-wide">
                <?php
                echo $form->label('Max Avatar Height', 'Garden.Profile.MaxHeight');
                echo wrap(t('Avatars will be scaled down if they exceed this height.'), 'div', array('class' => 'info')); ?>
            </div>
            <div class="input-wrap-right">
                <div class="textbox-suffix" data-suffix="<?php echo t('px'); ?>">
                    <?php echo $form->textBox('Garden.Profile.MaxHeight'); ?>
                </div>
            </div>
        </li>
    </ul>
    <?php echo $form->close('Save'); ?>
    <?php echo $this->data('AvatarSelectionOptions'); ?>
</section>
