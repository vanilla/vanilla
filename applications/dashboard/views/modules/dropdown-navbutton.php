
<div class="ButtonGroup <?php echo val('cssClass', $this); ?>">
    <ul class="Dropdown MenuItems">
        <?php foreach (val('items', $this) as $item) {
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
                    <a role="menuitem" rel="<?php echo val('rel', $item); ?>" class="dropdown-menu-link <?php echo val('cssClass', $item); ?>" tabindex="-1" href="<?php echo val('url', $item); ?>"><?php echo val('text', $item); ?></a>
                </li>
            <?php }
            if (val('type', $item) == 'divider') { ?>
                <li role="presentation" <?php if (val('cssClass', $item)) { ?> class="<?php echo val('cssClass', $item); ?>"<?php } ?>>
                    <hr />
                </li>
            <?php }
        } ?>
    </ul>
    <a href="#" class="NavButton Handle">
        <span><?php echo val('text', val('trigger', $this)); ?></span>
        <?php if (val('icon', val('trigger', $this))) {
            echo icon(val('icon', val('trigger', $this)));
        } ?>
    </a>
</div>
