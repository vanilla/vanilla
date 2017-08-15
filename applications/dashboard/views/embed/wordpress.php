<?php if (!defined('APPLICATION')) exit();
echo heading(t('Vanilla Plugin for WordPress'), '', '', [], '/embed/forum');
?>
<div class="media media-addon">
    <div class="media-left">
        <?php echo wrap(img('applications/dashboard/design/images/vanilla-wordpress.png', ['class' => 'PluginIcon']), 'div', ['class' => 'media-image-wrap']); ?>
    </div>
    <div class="media-body">
        <div class="media-heading"><div class="media-title"><?php echo t('Vanilla Plugin for WordPress'); ?></div>
            <div class="info">
            </div>
        </div>
        <div class="media-description">To embed your forum in a page on your WordPress site, grab our ready-made plugin from WordPress.org for easy integration.</div>
    </div>
    <div class="media-right media-options">
        <?php echo anchor('Get the Plugin', 'http://wordpress.org/extend/plugins/vanilla-forums/', 'btn btn-secondary'); ?>
    </div>
</div>
<div class="info padded">If you are not using WordPress, you can <?php echo anchor('use the universal code', 'embed/universal'); ?> for embedding your Vanilla Forum.</div>
