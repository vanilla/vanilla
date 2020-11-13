<?php
/** @var DropdownModule $dropdown */
$dropdown = $this;
$trigger = $dropdown->getTrigger();
$optionText = t('Options');
$accessibleLabel = !empty($trigger['text']) ? $trigger['text'] : $optionText;
$triggerID = $dropdown->getTriggerId();
?><span class="ToggleFlyout <?php echo $dropdown->getCssClass(); ?>"><?php
    if (($trigger['type'] ?? '') === 'button') :
    ?><span class="Button-Options" aria-label="<?php echo $accessibleLabel; ?>" id="<?php echo $triggerID; ?>">
        <span class="OptionsTitle" title="<?php echo $optionText; ?>">
            <?php echo $accessibleLabel; ?>
        </span>
        <?php echo sprite('SpFlyoutHandle', 'Arrow'); ?>
    </span>
    <?php else :
        $text = $trigger['text'] ?? false;
        $url = $trigger['url'] ?? false;
        $icon = $trigger['icon'] ?? false;
        $cssClass = $trigger['cssClass'] ?? false;
        $attributes = $trigger['attributes'] ?? false;
        $alert = !empty($dropdown->data('DashboardCount', '')) ? wrap($dropdown->data('DashboardCount', ''), 'span', ['class' => 'Alert']) : '';
        echo anchor($icon.$text.$alert, $url, $cssClass, $attributes);
    endif; ?>
    <ul class="Flyout MenuItems list-reset <?php echo $dropdown->getListCssClass(); ?>" role="menu" aria-labelledby="<?php echo $triggerID; ?>">
        <?php foreach($dropdown->getItems() as $item) {
            if (($item['type'] ?? '') == 'group') { ?>
                <li role="presentation" class="dropdown-header <?php echo $item['cssClass'] ?? ''; ?>">
                    <?php if ($iIcon = ($item['icon'] ?? false)) {
                        echo icon($iIcon);
                    }
                    echo $item['text'] ?? '';
                    if ($iBadge = ($item['badge'] ?? false)) {
                        echo badge($iBadge);
                    } ?>
                </li>
            <?php } ?>
            <?php  if (($item['type'] ?? '') == 'link') { ?>
                <li role="presentation" <?php
                    if ($ilistItemCssClass = ($item['listItemCssClass'] ?? false) || empty($item['icon'])) {
                       ?>class="<?php
                        echo trim($ilistItemCssClass.(empty($item['icon']) ? ' no-icon' : '')); ?>"<?php
                    } ?>>
                    <a role="menuitem" class="dropdown-menu-link <?php echo  $item['cssClass'] ?? ''; ?>" tabindex="-1" href="<?php echo url($item['url'] ?? ''); ?>" <?php echo attribute($item['attributes'] ?? []) ?>><?php
                        if ($iIcon = ($item['icon'] ?? false)) {
                            echo icon($iIcon);
                        }
                        echo $item['text'] ?? '';
                        if ($iBadge = ($item['badge'] ?? false)) {
                            echo ' '.wrap($iBadge, 'span', ['class' => 'Alert']);
                        }
                        ?></a>
                </li>
            <?php }
            if (($item['type'] ?? '') == 'divider') { ?>
                <li role="presentation" <?php if ($iCssClass = ($item['cssClass'] ?? false)) { ?> class="<?php echo $iCssClass; ?>"<?php } ?>>
                    <hr />
                </li>
            <?php }
        } ?>
    </ul>
</span>
