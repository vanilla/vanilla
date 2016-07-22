<div class="header-block">
    <div class="title-block">
        <?php echo anchor(dashboardSymbol('chevron-left'), "/embed/forum", 'btn btn-icon btn-return', ['aria-label' => t('Return')]); ?>
        <h1><?php echo t('Vanilla Plugin for WordPress'); ?></h1>
    </div>
</div>
<div class="media">
    <div class="media-left">
        <?php echo wrap(img('/applications/dashboard/design/images/addon-place-holder.png', array('class' => 'PluginIcon')), 'div', ['class' => 'addon-image-wrap']); ?>
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
