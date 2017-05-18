<?php /** @var MediaItemModule $this */ ?>
<div <?php echo attribute($this->getAttributes()); ?>>
    <?php if ($this->getImageHtml()) { ?>
        <div class="media-left">
            <div class="<?php echo val('image-wrap', $this->getCssClasses(), 'media-image-wrap'); ?>">
                <?php echo $this->getImageHtml(); ?>
            </div>
        </div>
    <?php } ?>
    <div class="media-body">
        <div class="media-title">
            <?php echo $this->getTitleUrl() != '' ? anchor(htmlspecialchars($this->getTitle()), $this->getTitleUrl(), 'reverse-link') : htmlspecialchars($this->getTitle()); ?>
        </div>
        <?php if ($this->getDescription()) { ?>
            <div class="media-description">
                <div class="description"><?php echo $this->getDescription(); ?></div>
            </div>
        <?php } ?>
        <?php if ($this->getMeta()) { ?>
            <div class="info">
                <?php echo implode('<span class="spacer">|</span>', $this->getMeta()); ?>
            </div>
        <?php } ?>
        <?php foreach ($this->getButtons() as $button) { ?>
            <a <?php echo attribute(val('attributes', $button)); ?> href="<?php echo val('url', $button); ?>">
                <?php echo val('text', $button); ?>
            </a>
        <?php } ?>
    </div>
</div>
