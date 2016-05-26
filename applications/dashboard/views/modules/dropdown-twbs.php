<div class="dropdown <?php echo val('cssClass', $this); ?>">
    <button class="btn btn-primary dropdown-toggle" type="button" id="<?php echo val('triggerId', $this); ?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        Dropdown
    </button>
    <div class="dropdown-menu" aria-labelledby="<?php echo val('triggerId', $this); ?>">
        <?php foreach (val('items', $this) as $item) {
            if (val('type', $item) == 'group') { ?>
                <h6 class="dropdown-header">
                    <?php if (val('icon', $item)) {
                        echo icon(val('icon', $item));
                    }
                    echo val('text', $item);
                    if (val('badge', $item)) {
                        echo badge(val('badge', $item));
                    } ?>
                </h6>
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
