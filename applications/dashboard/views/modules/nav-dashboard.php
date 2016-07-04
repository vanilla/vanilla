<?php
$items = val('items', $this);
if (!function_exists('renderDashboardNav')) {
    function renderDashboardNav($items)
    {
        foreach ($items as $item) {
            if (val('type', $item) == 'group') { ?>
                <h4 class="nav-heading"><a data-toggle="collapse" class="" href="#<?php echo trim(val('headerCssClass', $item))?>"><?php echo val('text', $item); ?></a></h4>
                <ul class="nav nav-pills nav-stacked collapse in" id="<?php echo trim(val('headerCssClass', $item)); ?>">
                <?php
                if (val('items', $item)) {
                    renderDashboardNav(val('items', $item));
                }
                echo '</ul>';
            }
            if (val('type', $item) == 'link') { ?>
                <li role="presentation" <?php if (val('listItemCssClass', $item)) { ?>class="nav-item <?php echo strtolower(val('listItemCssClass', $item)); ?>"<?php } ?>>
                    <a role="menuitem" class="nav-link <?php echo val('cssClass', $item).' '.strtolower(val('listItemCssClass', $item));?>" tabindex="-1" href="<?php echo val('url', $item); ?>">
                        <?php
                        if (val('icon', $item)) {
                            echo icon(val('icon', $item)).' ';
                        }
                        echo val('text', $item);
                        if (val('popinRel', $item)) {
                            echo ' <span class="Popin badge" rel="'.val('popinRel', $item).'"></span >';
                        }
                        ?>
                    </a>
		   <?php }?>
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
} ?>
<div class="nav-collapsible js-nav-collapsible">
    <?php renderDashboardNav($items); ?>
</div>
