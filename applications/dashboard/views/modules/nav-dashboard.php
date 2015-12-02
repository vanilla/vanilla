<?php
$items = val('items', $this);
if (!function_exists('renderNav')) {
    function renderDashboardNav($items)
    {
	foreach ($items as $item) {
	    if (val('type', $item) == 'group') { ?>
		<div class="Box Group <?php echo val('cssClass', $item); ?>">
		<h4><?php echo val('text', $item); ?></h4>
		<ul class="PanelInfo">
		<?php
		if (val('items', $item)) {
		    renderDashboardNav(val('items', $item));
		}
		echo '</ul></div>';
	    }
	    if (val('type', $item) == 'link') { ?>
		<li role="presentation"
		    <?php if (val('listItemCssClass', $item)) { ?>class="<?php echo val('listItemCssClass', $item); ?>"<?php } ?>>
		    <?php if (val('icon', $item)) {
			echo icon(val('icon', $item));
		    } ?>
		    <a role="menuitem" class="nav-link <?php echo val('cssClass', $item); ?>" tabindex="-1"
		       href="<?php echo val('url', $item); ?>"><?php echo val('text', $item); ?></a>
		    <?php if (val('badge', $item)) {
			echo badge(val('badge', $item));
		    } ?>
		</li>
	    <?php }
	    if (val('type', $item) == 'dropdown') {
		echo val('dropdownmenu', $item);
	    }
	    if (val('type', $item) == 'divider') { ?>
	    <li role="presentation" <?php if (val('cssClass', $item)) { ?> class="<?php echo val('cssClass', $item); ?>"<?php } ?>>
		<hr/>
	    </li>
	<?php }
	}
    }
}

renderDashboardNav($items);
