<?php
/** @var DropdownModule $dropdown */
$dropdown = $this;
$trigger = $dropdown->getTrigger();
?>

<div class="ButtonGroup <?php echo $dropdown->getCssClass(); ?>">
    <ul class="Dropdown MenuItems">
        <?php foreach ($dropdown->getItems() as $item) {
            if (val('type', $item) == 'group') { ?>
                <li role="presentation" class="dropdown-header <?php echo val('cssClass', $item); ?>">
                    <?php if (val('icon', $item)) {
                        echo icon(val('icon', $item));
                    }
                    echo val('text', $item);
                    if (val('badge', $item)) {
                        echo badge(val('badge', $item));
                    } ?>
                </li>
            <?php } ?>
            <?php  if (val('type', $item) == 'link') { ?>
                <li role="presentation" <?php if (val('listItemCssClass', $item)) { ?>class="<?php echo val('listItemCssClass', $item); ?>"<?php } ?>>
                    <a role="menuitem" rel="<?php echo val('rel', $item); ?>" class="dropdown-menu-link <?php echo val('cssClass', $item); ?>" tabindex="-1" href="<?php echo url(val('url', $item)); ?>"><?php echo val('text', $item); ?></a>
                </li>
            <?php }
            if (val('type', $item) == 'divider') { ?>
                <li role="presentation" <?php if (val('cssClass', $item)) { ?> class="<?php echo val('cssClass', $item); ?>"<?php } ?>>
                    <hr />
                </li>
            <?php }
        } ?>
    </ul>
    <a href="#" class="NavButton Handle <?php echo val('cssClass', $trigger); ?>">
        <span><?php echo val('text', $trigger); ?></span>
        <?php if (val('icon', $trigger)) {
            echo icon(val('icon', $trigger));
        } ?>
    </a>
</div>
