<?php
$sender = $this->Data('sender');

?>
<span class="ToggleFlyout OptionsMenu <?php echo $sender->dropdownCssClass; ?>">
    <span class="OptionsTitle" title="Options"><?php echo val('triggerText', $sender->trigger); ?></span>
    <span class="SpFlyoutHandle"></span>
    <ul class="Flyout MenuItems <?php echo $sender->listCssClass; ?>" role="menu" aria-labelledby="<?php echo $sender->triggerId; ?>">
        <?php foreach ($sender->items as $item) {
            if (val('type', $item) == 'group') { ?>
                <li role="presentation" class="dropdown-header <?php echo val('headerCssClass', $item); ?>">
                    <?php if (val('headerIcon', $item)) {
                        echo $sender->icon(val('headerIcon', $item));
                    }
                    echo val('headerText', $item);
                    if (val('headerBadge', $item)) {
                        echo $sender->badge(val('headerBadge', $item));
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
