<?php
/** @var NavModule $nav */
$nav = $this;

echo '<nav class="nav" role="navigation">';
$items = $nav->getItems();
if (!function_exists('renderNav')) {
    function renderNav($items) {
        foreach ($items as $item) {
	    if (val('type', $item) == 'group') {
		$heading = val('text', $item);
		if (!$heading) {
		    $item['cssClass'] .= ' nav-group-noheading';
		} ?>
		<div type="group" class="nav-group <?php echo val('cssClass', $item); ?>">
		<?php if ($heading) {
		    echo '<h3>'.val('text', $item).'</h3>';
		}
                if (val('items', $item)) {
                    renderNav(val('items', $item));
                }
		echo '</div>';
            }

            if (val('type', $item) == 'link') { ?>
		<a role="menuitem" class="nav-link <?php echo val('cssClass', $item); ?>" tabindex="-1"
		   href="<?php echo url(val('url', $item)); ?>">
		    <?php if (val('badge', $item)) {
			echo '<span class="Aside"><span class="Count">'.val('badge', $item).'</span></span>';
		    } ?>
                    <?php if (val('icon', $item)) {
                        echo icon(val('icon', $item));
                    } ?>
		    <?php echo '<span class="text">'.val('text', $item).'</span>'; ?>
		</a>
            <?php }
            if (val('type', $item) == 'dropdown') {
		echo val('dropdownmenu', $item);
	    }
	    if (val('type', $item) == 'divider') {
		echo '<hr/>';
            }
        }
    }
}
renderNav($items);
echo '</nav>';
