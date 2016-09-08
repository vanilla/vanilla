<?php /** @var ThemeInfoModule $this */ ?>

<div class="media media-callout CurrentTheme">
    <div class="media-left grid-item">
        <div class="image-wrap grid-image-wrap">
            <?php echo img($this->geticonUrl(), array('alt' => $this->getThemeName(), 'class' => 'grid-image')); ?>
        </div>
    </div>
    <div class="media-body">
        <?php if ($this->isCurrent()) { ?>
            <div class="flag"><?php echo t('Current Theme'); ?></div>
        <?php } ?>
        <div class="media-title">
            <h3 class="media-heading theme-name">
                <?php echo $this->getThemeUrl() != '' ? anchor($this->getThemeName(), $this->getThemeUrl()) : $this->getThemeName(); ?>
            </h3>
            <?php if (!empty($this->getInfo())) { ?>
                <div class="info">
                    <?php echo implode('<span class="spacer">|</span>', $this->getInfo()); ?>
                </div>
            <?php } ?>
        </div>
        <div class="media-description">
            <div class="description"><?php echo $this->getDescription(); ?></div>
            <?php if ($this->hasOptions() && $this->isCurrent()) { ?>
                <div class="options">
                    <?php echo sprintf(t('This theme has additional options.', 'This theme has additional options on the %s page.')
                        .anchor(t('Theme Options'), '/dashboard/settings/themeoptions')); ?>
                </div>
            <?php } ?>

            <!--            --><?php //if ($eventString = $this->getEventString()) {
            //                echo $eventString;
            //            } ?>

            <?php if ($this->hasUpgrade()) { ?>
                <div class="text-danger">
                    <?php echo sprintf(t('%1$s version %2$s is available.'), $this->getThemeName(), $this->getNewVersion()); ?>
                </div>
            <?php } ?>
        </div>
    </div>
</div>
