<?php if (!defined('APPLICATION')) exit(); ?>
<div class="ButtonGroup discussion-sort-filter-module pull-left">
    <span class="discussion-sorts">
    <?php
    foreach(DiscussionSortFilterModule::getSorts() as $key => $sort) {
        $cssClass = (DiscussionSortFilterModule::$sortFieldSelected == val('field', $sort, '')) ? DiscussionSortFilterModule::ACTIVE_CSS_CLASS : '';
        echo anchor(val('name', $sort), url(DiscussionSortFilterModule::getPath().DiscussionSortFilterModule::getSortFilterQueryString('', $key)), array('rel' => 'nofollow', 'class' => 'Button NavButton SortButton '.$cssClass)).' ';
    }
    ?>
    </span>
    <span class="discussion-filters">
    <?php DiscussionSortFilterModule::renderFilterDropdown(); ?>
    </span>
</div>
