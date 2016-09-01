<?php
echo '<nav class="nav nav-adventure" role="navigation">';
$items = val('items', $this);
if (!function_exists('renderAdventureNav')) {
    function renderAdventureNav($items) {
        foreach ($items as $item) {
            if (val('type', $item) == 'group') {
                $heading = val('text', $item);
                if (!$heading) {
                    $item['cssClass'] .= ' nav-group-noheading';
                } ?>
                <div type="group" class="nav-group <?php echo val('cssClass', $item); ?>">
                <?php
                if ($heading) {
                    echo '<h3>'.val('text', $item).'</h3>';
                }
                if (val('items', $item)) {
                    renderAdventureNav(val('items', $item));
                }
                echo '</div>';
            }
            if (val('type', $item) == 'link') { ?>
                <div class="nav-item">
                    <a role="menuitem" class="nav-link <?php echo val('cssClass', $item); ?>" tabindex="-1"
                        href="<?php echo val('url', $item); ?>">
                        <div class="nav-item-icon"><?php echo icon(val('icon', $item)); ?></div>
                        <div class="nav-item-content">
                            <div class="nav-item-title"><?php echo val('text', $item); ?></div>
                            <div class="nav-item-description">
                                <?php echo val('description', $item); ?>
                            </div>
                        </div>
                        <div class="nav-item-arrow"><?php echo dashboardSymbol('caret-right'); ?></div>
                    </a>
                </div>
                <?php }
            if (val('type', $item) == 'divider') {
                echo '<hr/>';
            }
        }
    }
}
renderAdventureNav($items);
echo '</nav>';
