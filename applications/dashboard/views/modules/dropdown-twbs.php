<?php
/** @var DropdownModule $dropdown */
$dropdown = $this;
$trigger = $dropdown->getTrigger();
?>

<div class="dropdown <?php echo $dropdown->getCssClass() ?>">
    <<?php echo val('type', $trigger); ?> class="dropdown-toggle <?php echo val('cssClass', $trigger); ?>" id="<?php echo $dropdown->getTriggerId(); ?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" <?php echo attribute(val('attributes', $trigger)); ?>>
        <?php echo val('text', $trigger); ?>
    </<?php echo val('type', $trigger); ?>>
    <div class="dropdown-menu <?php echo $dropdown->getListCssClass(); ?>" role="menu" aria-labelledby="<?php echo $dropdown->getTriggerId(); ?>">
        <?php foreach($dropdown->getItems() as $item) {
            if (val('type', $item) == 'group') { ?>
                <div class="dropdown-header" <?php echo attribute(val('attributes', $item, [])) ?>>
                    <?php if (val('icon', $item)) {
                        echo icon(val('icon', $item));
                    }
                    echo val('text', $item);
                    if (val('badge', $item)) {
                        echo badge(val('badge', $item));
                    } ?>
                </div>
            <?php } ?>
            <?php  if (val('type', $item) == 'link') { ?>
                <a role="menuitem" class="dropdown-item <?php echo val('cssClass', $item); ?>" tabindex="-1" href="<?php echo url(val('url', $item)); ?>"  <?php echo attribute(val('attributes', $item, [])) ?>>
                    <?php if (val('icon', $item)) {
                        echo icon(val('icon', $item));
                    }
                    echo val('text', $item);
                    if (val('badge', $item)) {
                        echo badge(val('badge', $item));
                    } ?>
                </a>
            <?php }
            if (val('type', $item) == 'divider') { ?>
                <div class="dropdown-divider <?php echo val('cssClass', $item); ?>"></div>
            <?php }
        } ?>
    </div>
</div>
