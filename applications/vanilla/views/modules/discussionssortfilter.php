<?php if (!defined('APPLICATION')) exit(); ?>
<div class="ButtonGroup discussion-sort-filter-module pull-left">
    <?php if ($this->showSorts()) { ?>
        <span class="discussion-sorts">
        <?php foreach ($this->getSortData() as $sort) {
            echo anchor(val('name', $sort), val('url', $sort), [
                    'rel' => val('rel', $sort),
                    'class' => 'btn-default Button NavButton SortButton ' . val('cssClass', $sort, '')
                ]) . ' ';
        }
        ?>
        </span>
    <?php }
    if ($this->showFilters()) { ?>
        <span class="discussion-filters">
        <?php
        foreach ($this->getFilterDropdowns() as $dropdown) {
            $label = val('name', val(0, $dropdown), "Filter");
            foreach ($dropdown as $link) {
                if (val('active', $link)) {
                    $label = val('name', $link);
                }
            }
            echo linkDropDown($dropdown, 'selectBox-ideationFilter', 'Sort');
        } ?>
        </span>
    <?php } ?>
</div>
