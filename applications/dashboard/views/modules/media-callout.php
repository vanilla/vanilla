<?php /** @var MediaItemModule $this */ ?>
<div <?php echo attribute($this->getAttributes()); ?>>
    <?php if ($this->getImageHtml()) { ?>
        <div class="media-left">
            <div class="media-image-wrap">
                <?php echo $this->getImageHtml(); ?>
            </div>
        </div>
    <?php } ?>
    <div class="media-body">
        <?php if (val('is-current', $this->getOptions())) { ?>
            <div class="flag"><?php echo t('Current Theme'); ?></div>
        <?php } ?>
        <div class="media-heading">
            <h3 class="media-title theme-name">
                <?php echo $this->getTitleUrl() != '' ? anchor($this->getTitle(), $this->getTitleUrl()) : $this->getTitle(); ?>
            </h3>
            <?php if ($this->getMeta()) { ?>
                <div class="info">
                    <?php echo implode('<span class="spacer">|</span>', $this->getMeta()); ?>
                </div>
            <?php } ?>
        </div>
        <div class="media-description">
            <p class="description"><?php echo $this->getDescription(); ?></p>
            <?php if (val('has-options', $this->getOptions()) && val('is-current', $this->getOptions())) { ?>
                <p class="options">
                    <?php echo sprintf(t('This theme has additional options.', 'This theme has additional options on the %s page.'), anchor(t('Theme Options'), '/dashboard/settings/themeoptions')); ?>
                </p>
            <?php } ?>
            <?php if (val('has-upgrade', $this->getOptions())) { ?>
                <p class="text-danger">
                    <?php echo sprintf(t('%1$s version %2$s is available.'), $this->getTitle(), val('new-version', $this->getOptions())); ?>
                </p>
            <?php } ?>
        </div>
    </div>
</div>
