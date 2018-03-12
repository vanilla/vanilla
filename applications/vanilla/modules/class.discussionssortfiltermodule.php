<?php
/**
 * Discussions Sort/Filter module
 *
 * @copyright 2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Vanilla
 * @author Becky Van Bussel
 * @since 2.2
 */

/**
 * DiscussionsSortFilterModule
 *
 * Renders a sort/filter module on a discussions view based on the sorts and filters in the Discussion Model.
 * If there are category-specific filters, the categoryID property must be set in order for it to render the
 * filters for the category.
 *
 */
class DiscussionsSortFilterModule extends Gdn_Module {

    const ACTIVE_CSS_CLASS = 'Active active';

    /** @var array The sorts to render. */
    protected $sorts;

    /** @var array The filters to render. */
    protected $filters;

    /** @var string The selected sort. */
    protected $selectedSort;

    /** @var array The selected filters. */
    protected $selectedFilters;

    /** @var bool Whether to show the sorting options in the view. */
    protected $showSorts = true;

    /** @var bool Whether to show the filter options in the view. */
    protected $showFilters = true;

    /** @var int The ID of the category we're in. */
    protected $categoryID;

    /** @var string The view of the dropdown module to render. */
    protected $dropdownView = 'dropdown-navbutton';

    /**
     * @param int $categoryID The ID of the category we're in.
     * @param string $selectedSort The selected sort.
     * @param array $selectedFilters The selected filters.
     */
    public function __construct($categoryID = 0, $selectedSort = '', $selectedFilters = []) {
        parent::__construct();
        if ($categoryID) {
            $this->categoryID = $categoryID;
        }

        $this->selectedSort = $selectedSort;
        $this->selectedFilters = $selectedFilters;
    }

    /**
     * @param $showSorts Whether to show the sorting options in the view.
     * @return DiscussionsSortFilterModule $this
     */
    public function setShowSorts($showSorts) {
        $this->showSorts = $showSorts;
        return $this;
    }

    /**
     * @param $showFilters Whether to show the filtering options in the view.
     * @return DiscussionsSortFilterModule $this
     */
    public function setShowFilters($showFilters) {
        $this->showFilters = $showFilters;
        return $this;
    }

    /**
     * @return bool Whether to show the sorting options in the view.
     */
    public function showSorts() {
        return $this->showSorts;
    }

    /**
     * @return bool Whether to show the filtering options in the view.
     */
    public function showFilters() {
        return $this->showFilters;
    }

    /**
     * Checks whether we should even render this whole thing.
     *
     * @return bool Whether to render the module.
     */
    public function prepare() {
        $this->sorts = DiscussionModel::getAllowedSorts();
        $this->filters = DiscussionModel::getAllowedFilters();
        return !empty($this->sorts) || !empty($this->filters);
    }

    /**
     * @param string $dropdownView The view of the dropdown module to render.
     * @return DiscussionsSortFilterModule $this
     */
    public function setDropdownView($dropdownView) {
        $this->dropdownView = $dropdownView;
        return $this;
    }

    /**
     * Returns an array of sanitized sort data for the view.
     * (Data uses no rendering module and must be manually inserted into the view.)
     *
     * @return array An array of sorts consisting of the name, url, rel and cssClass of each sort item.
     */
    protected function getSortData() {
        $sortData = [];
        foreach($this->sorts as $sort) {
            // Check to see if there's a category restriction.
            if ($categories = val('categories', $sort)) {
                if (!in_array($this->categoryID, $categories)) {
                    continue;
                }
            }
            $key = val('key', $sort);
            $queryString = val('key', $sort) !== DiscussionModel::EMPTY_FILTER_KEY ? DiscussionModel::getSortFilterQueryString($this->selectedSort, $this->selectedFilters, $key) : '';
            $sortData[$key]['name'] = val('name', $sort);
            $sortData[$key]['url'] = $this->getPagelessPath().$queryString;
            $sortData[$key]['rel'] = 'nofollow';
        }
        if (val($this->selectedSort, $sortData)) {
            $sortData[$this->selectedSort]['cssClass'] = self::ACTIVE_CSS_CLASS;
            $sortData[$this->selectedSort]['active'] = true;
        } elseif (val($sortKey = DiscussionModel::getDefaultSortKey(), $sortData)) {
            $sortData[$sortKey]['cssClass'] = self::ACTIVE_CSS_CLASS;
        }

        return $sortData;
    }

    /**
     * Returns an array of dropdown menus with the data from the filters array or an array containing an empty string
     * to make it safe for echoing out.
     *
     * @return array An array of dropdown menus or an array containing an empty string.
     */
    protected function getFilterDropdowns() {
        if (!$this->filters) {
            return [''];
        }

        $filterDropdown = [];

        foreach($this->filters as $filter) {
            // Check to see if there's a category restriction.
            if (!empty($filter['categories'])) {
                if (!in_array($this->categoryID, $filter['categories'])) {
                    continue;
                }
            }

            $key = $filter['key'];

            $selected = $this->selectedFilters[$key] ?? null;

            $currentGroup = null;
            $path = $this->getPagelessPath();
            $values = array_values($filter['filters']);
            $totalValues = count($values);

            for ($i = 0; $i < $totalValues; $i++) {
                $value = $values[$i];
                $valueKey = $value['key'];
                $query = DiscussionModel::getSortFilterQueryString(
                    $this->selectedSort,
                    $this->selectedFilters,
                    '',
                    [$key => $valueKey]
                );

                $url = $path.$query;
                if (empty($url)) {
                    $url = '/';
                }

                $group = $value['group'] ?? null;
                if($i > 0 && $currentGroup !== $group) {
                    $filterDropdown[] = [
                        'separator' => true
                    ];
                }

                if ($selected === null && $i === 0) {
                    $isActive = true;
                } elseif ($selected === $valueKey) {
                    $isActive = true;
                } else {
                    $isActive = false;
                }

                $filterDropdown[] = [
                    'name' => $value['name'],
                    'url' => url($url),
                    'active' => $isActive,
                ];

                $currentGroup = $group;
            }

            $dropdowns[] = $filterDropdown;
        }

        return $dropdowns;
    }

    /**
     * Returns the current path without any page indicator. Useful for resetting sorting/filtering no matter
     * which page the user is on.
     *
     * @return string The path of the request without the page.
     */
    protected function getPagelessPath() {
        // Remove page indicator.
        return preg_replace('/\/p\d$/i', '', Gdn::request()->path());
    }
}
