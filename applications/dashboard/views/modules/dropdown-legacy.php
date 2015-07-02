
<span class="ToggleFlyout OptionsMenu <?php echo $this->dropdownCssClass; ?>">
    <span class="OptionsTitle" title="Options"><?php echo val('triggerText', $this->trigger); ?></span>
    <span class="SpFlyoutHandle"></span>
    <ul class="Flyout MenuItems <?php echo $this->listCssClass; ?>" role="menu" aria-labelledby="<?php echo $this->triggerId; ?>">
        <?php foreach ($this->items as $item) {
            if (val('type', $item) == 'group') { ?>
                <li role="presentation" class="dropdown-header <?php echo val('headerCssClass', $item); ?>">
                    <?php if (val('headerIcon', $item)) {
                        echo icon(val('headerIcon', $item));
                    }
                    echo val('headerText', $item);
                    if (val('headerBadge', $item)) {
                        echo badge(val('headerBadge', $item));
                    } ?>
                </li>
            <?php } ?>
            <?php  if (val('type', $item) == 'link') { ?>
                <li role="presentation" <?php if (val('listItemCssClass', $item)) { ?>class="<?php echo val('listItemCssClass', $item); ?>"<?php } ?>>
                    <a role="menuitem" class="dropdown-menu-link <?php echo val('linkCssClass', $item); ?>" tabindex="-1" href="<?php echo val('linkUrl', $item); ?>"><?php echo val('linkText', $item); ?></a>
                </li>
            <?php }
            if (val('type', $item) == 'divider') { ?>
                <li role="presentation" <?php if (val('dividerCssClass', $item)) { ?> class="<?php echo val('dividerCssClass', $item); ?>"<?php } ?>>
                    <hr />
                </li>
            <?php }
        } ?>
    </ul>
</span>
