<div class="dropdown <?php echo val('cssClass', $this); ?>">
    <div class="dropdown-toggle" id="<?php echo val('triggerId', $this); ?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <?php
        $trigger = val('trigger', $this);
        echo val('text', $trigger);
        ?>
    </div>
    <div class="dropdown-menu <?php echo val('listCssClass', $this); ?>" aria-labelledby="<?php echo val('triggerId', $this); ?>">
        <?php foreach (val('items', $this) as $item) {
            if (val('type', $item) == 'group') { ?>
                <div class="dropdown-header">
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
                <a role="menuitem" class="dropdown-item <?php echo val('cssClass', $item); ?>" tabindex="-1" href="<?php echo val('url', $item); ?>">
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
