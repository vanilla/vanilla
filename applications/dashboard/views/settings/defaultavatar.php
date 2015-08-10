<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo t('Default Avatar'); ?></h1>
<?php
//$this->Form = new Gdn_Form();
echo $this->Form->open(array('enctype' => 'multipart/form-data'));
echo $this->Form->errors();
$defaultAvatar = c('Garden.DefaultAvatar');
$session = Gdn::session();
?>
<?php
if ($defaultAvatar) {
    echo val('crop', $this->Data);
} else { ?>
    <div class="avatars">
        <div class="Padded current-avatar">
            <?php echo img($this->data('avatar'), array('style' => 'min-width: '.c('Garden.Thumbnail.Size').'px; min-height: '.c('Garden.Thumbnail.Size').'px;')); ?>
        </div>
    </div>
<?php }?>
<div class="js-new-avatar Button" style="margin-bottom: 20px;">Upload New Avatar</div>
<?php
echo $this->Form->input('DefaultAvatar', 'file', array('class' => 'js-new-avatar-upload Hidden'));
if ($defaultAvatar) {
    echo wrap(anchor(t('Remove Default Avatar'), '/dashboard/settings/removedefaultavatar/'.$session->TransientKey(), 'Button'), 'div');
}
?>
<?php echo $this->Form->close(); ?>

