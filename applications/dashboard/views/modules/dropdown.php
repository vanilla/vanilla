<?php
/** @var DropdownModule $dropdown */
$dropdown = $this;
$trigger = $dropdown->getTrigger();
?><span class="ToggleFlyout <?php echo $dropdown->getCssClass(); ?>"><?php
    if (val('type', $trigger) === 'button') :
    ?><span class="Button-Options">
        <span class="OptionsTitle" title="<?php echo t('Options'); ?>">
            <?php echo val('text', $trigger); ?>
        </span>
        <?php echo sprite('SpFlyoutHandle', 'Arrow'); ?>
    </span>
    <?php else :
        $text = val('text', $trigger);
        $url = val('url', $trigger);
        $icon = val('icon', $trigger);
        $cssClass = val('cssClass', $trigger);
        $attributes = val('attributes', $trigger);
        $alert = !empty($dropdown->data('DashboardCount', '')) ? wrap($dropdown->data('DashboardCount', ''), 'span', ['class' => 'Alert']) : '';
        echo anchor($icon.$text.$alert, $url, $cssClass, $attributes);
    endif; ?>
    <ul class="Flyout MenuItems list-reset <?php echo $dropdown->getListCssClass(); ?>" role="menu" aria-labelledby="<?php echo $dropdown->getTriggerId(); ?>">
        <?php foreach($dropdown->getItems() as $item) {
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
                            echo ' '.wrap(val('badge', $item), 'span', ['class' => 'Alert']);
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
