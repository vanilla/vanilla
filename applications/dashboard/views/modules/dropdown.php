
<span class="ToggleFlyout OptionsMenu <?php echo val('cssClass', $this); ?>">
    <?php if (val('type', val('trigger', $this)) === 'button') : ?>
    <span class="Button-Options">
        <span class="OptionsTitle" title="<?php echo t('Options'); ?>">
            <?php echo val('text', val('trigger', $this)); ?>
        </span>
        <?php echo sprite('SpFlyoutHandle', 'Arrow'); ?>
    </span>
    <?php else :
        $text = val('text', val('trigger', $this));
        $url = val('url', val('trigger', $this));
        $icon = val('icon', val('trigger', $this));
        $cssClass = val('cssClass', val('trigger', $this));
        $attributes = val('attributes', val('trigger', $this));
        $alert = !empty($this->data('DashboardCount', '')) ? wrap($this->data('DashboardCount', ''), 'span', ['class' => 'Alert']) : '';
        echo anchor($icon.$text.$alert, $url, $cssClass, $attributes);
    endif; ?>
    <ul class="Flyout MenuItems list-reset <?php echo val('listCssClass', $this); ?>" role="menu" aria-labelledby="<?php echo val('triggerId', $this); ?>">
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
                <li role="presentation" <?php if (val('listItemCssClass', $item) || empty($item['icon'])) { ?>class="<?php echo trim(val('listItemCssClass', $item).(empty($item['icon']) ? ' no-icon' : '')); ?>"<?php } ?>>
                    <a role="menuitem" class="dropdown-menu-link <?php echo val('cssClass', $item); ?>" tabindex="-1" href="<?php echo url(val('url', $item)); ?>" <?php echo attribute(val('attributes', $item, [])) ?>><?php
                        if (val('icon', $item)) {
                            echo icon(val('icon', $item));
                        }
                        echo val('text', $item);
                        if (val('badge', $item)) {
                            echo badge(val('badge', $item));
                        }
                        ?></a>
                </li>
            <?php }
            if (val('type', $item) == 'divider') { ?>
                <li role="presentation" <?php if (val('cssClass', $item)) { ?> class="<?php echo val('cssClass', $item); ?>"<?php } ?>>
                    <hr />
                </li>
            <?php }
        } ?>
    </ul>
</span>
