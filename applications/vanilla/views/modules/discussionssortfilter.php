<?php if (!defined('APPLICATION')) exit(); ?>
<div class="ButtonGroup discussion-sort-filter-module pull-left">
    <span class="discussion-sorts">
    <?php
    foreach ($this->getSortData() as $sort) {
        echo anchor(val('name', $sort), val('url', $sort), array(
                    'rel' => val('rel', $sort),
                    'class' => 'btn-default Button NavButton SortButton '.val('cssClass', $sort, '')
                )).' ';
    }
    ?>
    </span>
    <span class="discussion-filters">
    <?php foreach ($this->getFilterDropdowns() as $dropdown) {
        echo $dropdown;
    } ?>
    </span>
</div>
