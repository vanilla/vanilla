<?php
    if (!defined('APPLICATION')) exit();
    echo heading(t('Email Styles'), t('Send a Test Email'), '/dashboard/settings/emailtest', 'js-modal btn btn-primary');
    $isTextFormat = strtolower(c('Garden.Email.Format', 'text') === 'text');
    $fullPostIsEnabled = c('Vanilla.Email.FullPost', false);
?>
<div class="row form-group">
    <div class="label-wrap-wide">
        <?php echo '<div class="label">'.t('Include full post in email notifications').'</div>'; ?>
        <div class="info"><?php echo t('If enabled, the full content of posts will be sent in email notifications to users.'); ?></div>
    </div>
    <div class="input-wrap-right">
        <span id="enable-email-full-post-toggle">
            <?php
            if ($fullPostIsEnabled) {
                echo wrap(anchor('<div class="toggle-well"></div><div class="toggle-slider"></div>', '/dashboard/settings/toggleemailfullpost/0', 'Hijack'), 'span', ['class' => "toggle-wrap toggle-wrap-on"]);
            } else {
                echo wrap(anchor('<div class="toggle-well"></div><div class="toggle-slider"></div>', '/dashboard/settings/toggleemailfullpost/1', 'Hijack'), 'span', ['class' => "toggle-wrap toggle-wrap-off"]);
            }
            ?>
        </span>
    </div>
</div>
<div class="row form-group">
    <div class="label-wrap-wide">
        <?php echo '<div class="label">'.t('Enable HTML emails').'</div>'; ?>
        <div class="info"><?php echo t('Spruce up your emails by adding a logo and customizing the colors.'); ?></div>
    </div>
    <div class="input-wrap-right">
        <span id="plaintext-toggle">
            <?php
            if ($isTextFormat) {
                echo wrap(anchor('<div class="toggle-well"></div><div class="toggle-slider"></div>', '/dashboard/settings/setemailformat/html', 'Hijack'), 'span', ['class' => "toggle-wrap toggle-wrap-off"]);
            } else {
                echo wrap(anchor('<div class="toggle-well"></div><div class="toggle-slider"></div>', '/dashboard/settings/setemailformat/text', 'Hijack'), 'span', ['class' => "toggle-wrap toggle-wrap-on"]);
            }
            ?>
        </span>
    </div>
</div>
<div class="html-email-settings js-foggy" <?php echo $isTextFormat ? 'data-is-foggy="true"' : 'data-is-foggy="false"'; ?>>
    <?php $this->data('ConfigurationModule')->render(); ?>
</div>
