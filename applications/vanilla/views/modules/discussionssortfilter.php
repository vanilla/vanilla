<?php if (!defined('APPLICATION')) exit(); ?>
<div class="ButtonGroup discussion-sort-filter-module pull-left">
    <?php if ($this->showSorts()) { ?>
        <span class="discussion-sorts">
        <?php foreach ($this->getSortData() as $sort) {
            echo anchor(val('name', $sort), val('url', $sort), array(
                    'rel' => val('rel', $sort),
                    'class' => 'btn-default Button NavButton SortButton ' . val('cssClass', $sort, '')
                )) . ' ';
        }
        ?>
        </span>
    <?php }
    if ($this->showFilters()) { ?>
        <span class="discussion-filters">
        <?php foreach ($this->getFilterDropdowns() as $dropdown) {
            echo $dropdown;
        } ?>
        </span>
    <?php } ?>
</div>
