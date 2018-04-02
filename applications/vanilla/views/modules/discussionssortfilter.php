<?php if (!defined('APPLICATION')) exit(); ?>
<div class="ButtonGroup discussion-sort-filter-module pull-left">
    <?php if ($this->showSorts()) { ?>
        <span class="discussion-refine discussion-sorts">
        <?php
            $sortLinks = [];
            $foundActiveLink = false;

            foreach ($this->getSortData() as $sort) {
                $isActive = val('active', $sort);
                $sortLinks[] = [
                    'name' => val('name', $sort),
                    'url' => url('/'.val('url', $sort)),
                    'active' => $isActive,
                ];

                if ($isActive) {
                    $foundActiveLink = true;
                }
            }

            if(!$foundActiveLink) {
                $sortLinks[0]['active'] = true;
            }

            echo linkDropDown($sortLinks, 'selectBox-discussionsSortFilter', 'Sort');
        ?>
        </span>
    <?php }
    if ($this->showFilters()) { ?>
        <span class="discussion-refine discussion-filters">
        <?php
        foreach ($this->getFilterDropdowns() as $dropdown) {
            $label = val('name', val(0, $dropdown), 'Filter');
            foreach ($dropdown as $link) {
                if (val('active', $link)) {
                    $label = val('name', $link);
                }
            }
            echo linkDropDown($dropdown, 'selectBox-discussionsSortFilter', 'Filter');
        } ?>
        </span>
    <?php } ?>
</div>
