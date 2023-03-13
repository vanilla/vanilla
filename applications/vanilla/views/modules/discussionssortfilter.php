<?php if (!defined("APPLICATION")) {
    exit();
} ?>
<div class="ButtonGroup discussion-sort-filter-module pull-left">
    <?php
    if ($this->showSorts()) { ?>
        <span class="discussion-refine discussion-sorts">
        <?php
        $sortLinks = [];
        $foundActiveLink = false;

        foreach ($this->getSortData() as $sort) {
            $sortLinks[] = [
                "name" => t(val("name", $sort)),
                "url" => url("/" . val("url", $sort)),
                "active" => val("active", $sort),
            ];
        }

        echo linkDropDown($sortLinks, "Sort", "selectBox-discussionsSortFilter");
        ?>
        </span>
    <?php }
    if ($this->showFilters()) { ?>
        <span class="discussion-refine discussion-filters">
        <?php foreach ($this->getFilterDropdowns() as $dropdown) {
            $label = val("name", val(0, $dropdown), "Filter");
            foreach ($dropdown as $link) {
                if (val("active", $link)) {
                    $label = val("name", $link);
                }
            }
            echo linkDropDown($dropdown, "Filter", "selectBox-discussionsSortFilter");
        } ?>
        </span>
    <?php }
    ?>
</div>
