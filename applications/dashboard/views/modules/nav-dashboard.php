<?php
/** @var NavModule $nav */
$nav = $this;
$items = $nav->getItems();
if (!function_exists('renderDashboardNav')) {
    function renderDashboardNav($items) {
        foreach ($items as $item) {
            if (val('type', $item) == 'group') {
                if (val('text', $item)) { ?>
                    <h4 class="nav-heading"><a data-toggle="collapse" aria-expanded="<?php echo val('ariaExpanded', $item); ?>" class="js-save-pref-collapse <?php echo val('collapsed', $item); ?> <?php echo val('headerCssClass', $item); ?>" data-key="<?php echo val('headerCssClass', $item); ?>" href="#<?php echo trim(val('headerCssClass', $item))?>"><?php echo val('text', $item); ?></a></h4>
                <?php } ?>
                <ul class="nav nav-pills nav-stacked collapse <?php echo val('collapsedList', $item); ?> <?php echo trim(val('cssClass', $item)); ?>" id="<?php echo trim(val('headerCssClass', $item)); ?>">
                <?php
                if (val('items', $item)) {
                    renderDashboardNav(val('items', $item));
                }
                echo '</ul>';
            }
            if (val('type', $item) == 'link') { ?>
                <li role="presentation" <?php if (val('listItemCssClass', $item)) { ?>class="nav-item <?php echo strtolower(val('listItemCssClass', $item)); ?>"<?php } ?>>
                    <a role="menuitem" data-section="<?php echo val('section', $item); ?>" data-link-path="<?php echo val('url', $item); ?>" class="js-save-pref-section-landing-page nav-link <?php echo val('cssClass', $item).' '.strtolower(val('listItemCssClass', $item));?>" tabindex="-1" href="<?php echo url(val('url', $item)); ?>">
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
} ?>
<nav class="nav-collapsible js-nav-collapsible">
    <?php renderDashboardNav($items); ?>
</nav>
