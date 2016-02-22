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
 * Renders a sort/filter module on a discussions view. Currently only supported in /categories discussions views.
 *
 */
class DiscussionsSortFilterModule extends Gdn_Module {

    const ACTIVE_CSS_CLASS = 'Active active';

    /**
     * @var array The sorts to add to the module. Each sort corresponds with an order by clause.
     *
     * Each sort in the array has the following properties:
     * - **key**: string - The key name of the sort. Appears in the query string, should be url-friendly.
     * - **name**: string - The display name of the sort.
     * - **orderBy**: string - An array indicating order by fields and their directions in the format: array('field1' => 'direction', 'field2' => 'direction')
     */
    protected static $sorts = array(
        'hot' => array('key' => 'hot', 'name' => 'Hot', 'orderBy' => array('d.DateLastComment' => 'desc')),
        'top' => array('key' => 'top', 'name' => 'Top', 'orderBy' => array('d.Score' => 'desc', 'd.DateInserted' => 'desc')),
        'new' => array('key' => 'new', 'name' => 'New', 'orderBy' => array('d.DateInserted' => 'desc'))
    );

    /**
     * @var array The filters to add to the module. Each filter corresponds with a where clause.
     * The array is eventually rendered in a dropdown menu using the DropdownModule.
     *
     * Each filter in the array has the following properties:
     * - **key**: string - The key name of the filter. Appears in the query string, should be url-friendly.
     * - **name**: string - The display name of the filter. Appears as an option in the dropdown menu.
     * - **where**: string - The where array query to execute for the filter. Uses
     * - **group**: string - (optional) The dropdown module will group together any items with the same group name.
     */
    protected static $filters;

    /**
     * @var string Stores the value from the last sort retrieval method called
     *      (either getSortFromRequest or getSortFromUserPreference).
     */
    protected static $sortKeySelected;

    /**
     * @var string Stores the value from the last filter retrieval method called
     *      (either getFilterFromRequest or getFilterFromUserPreference).
     */
    protected static $filterKeySelected;

    public function __construct() {}

    /**
     * @return array The current sort array.
     */
    public static function getSorts() {
        return self::$sorts;
    }

    /**
     * @return array The current filter array.
     */
    public static function getFilters() {
        return self::$filters;
    }

    /**
     * Checks whether we should even render this whole thing.
     *
     * @return bool Whether to render the module.
     */
    public function prepare() {
        return !empty(self::$sorts) || !empty(self::$filters);
    }

    /**
     * Returns an array of sanitized sort data for the view.
     * (Data uses no rendering module and must be manually inserted into the view.)
     *
     * @return array An array of sorts consisting of the name, url, rel and cssClass of each sort item.
     */
    protected function getSortData() {
        $sortData = array();
        foreach(self::$sorts as $sort) {
            $key = val('key', $sort);
            $sortData[$key]['name'] = val('name', $sort);
            $sortData[$key]['url'] = self::getPagelessPath().self::sortFilterQueryString('', $key);
            $sortData[$key]['rel'] = 'nofollow';
            if (self::$sortKeySelected == val('key', $sort)) {
                $sortData[$key]['cssClass'] = DiscussionsSortFilterModule::ACTIVE_CSS_CLASS;
            }
        }
        return $sortData;
    }

    /**
     * Returns a dropdown menu with the data from the filters array or an empty string to make it safe for echoing out.
     *
     * @return DropdownModule|string The filters dropdown menu or an empty string.
     */
    protected static function getFilterDropdown() {
        if (!self::getFilters()) {
            return '';
        }
        $dropdown = new DropdownModule('discussions-filter', sprintf(t('All %s'), t('Discussions')), 'discussion-filter');

        // Override the trigger text?
        if (self::$filterKeySelected && self::$filterKeySelected != 'none') {
            $selected = val('name', val(self::$filterKeySelected, self::getFilters()));
            $dropdown->setTrigger($selected);
        }

        $dropdown->setView('dropdown-navbutton'); // TODO make this a property?
        $dropdown->setForceDivider(true); // Adds dividers between groups in the dropdown.

        // Add the filters to the dropdown
        foreach(self::$filters as $filter) {
            $key = val('group', $filter, '').'.'.val('key', $filter);
            $dropdown->addLink(
                val('name', $filter),
                url(self::getPagelessPath().self::sortFilterQueryString(val('key', $filter))),
                $key
            );
        }
        return $dropdown;
    }

    /**
     * Retrieves the sort key from the query and returns the corresponding sort.
     *
     * @return array The sort associated with the sort query string value.
     */
    public static function getSortFromRequest() {
        if ($sortCode = Gdn::request()->get('sort')) {
            $sort = val($sortCode, self::getSorts());

            if ($sort) {
                self::$sortKeySelected = val('key', $sort);
                self::setUserSortPreferences(val('key', $sort));
                return $sort;
            }
        }
        return array();
    }

    /**
     * Retrieves the filter key from the query and returns the corresponding filter.
     *
     * @return array The filter associated with the filter query string value.
     */
    public static function getFilterFromRequest() {
        if ($filterCode = Gdn::request()->get('filter')) {
            $filter = val($filterCode, self::getFilters());

            if ($filter) {
                self::$filterKeySelected = val('key', $filter);
                if ($categoryID = val('CategoryID', Gdn::controller())) {
                    self::setUserFilterPreferences(val('key', $filter), $categoryID);
                }
                return $filter;
            }
        }
        return array();
    }

    /**
     * Retrieves the current user's sorting preference. The preference is usually based on the last sort the user made.
     *
     * @return array The sort associated with the preference key stored or an empty array if not found.
     */
    public static function getSortFromUserPreference() {
        if (Gdn::session()->isValid()) {
            $sortKey = Gdn::session()->GetPreference('Discussions.SortKey');
            if ($sortKey) {
                self::$sortKeySelected = $sortKey;
                return val($sortKey, self::$sorts, array());
            }
        }
        return array();
    }

    /**
     * Retrieves the current user's filtering preference for a category.
     * The category preference is usually based on the last filter the user made on the category.
     *
     * @param $categoryID The ID of the category to retrieve the filter preference for.
     * @return array The filter associated with the preference key stored or an empty array if not found.
     */
    public static function getFilterFromUserPreference($categoryID) {
        if (Gdn::session()->isValid()) {
            $filterKey = Gdn::session()->GetPreference('Category.'.$categoryID.'.FilterKey');
            if ($filterKey) {
                self::$filterKeySelected = $filterKey;
                return val($filterKey, self::$filters, array());
            }
        }
        return array();
    }

    /**
     * Saves the preference for sorting on the user. The preference is usually based on the last sort the user made.
     *
     * @param $sortKey The preferred sort key.
     */
    protected static function setUserSortPreferences($sortKey) {
        if (Gdn::session()->isValid()) {
            Gdn::userModel()->SavePreference(Gdn::session()->UserID, 'Discussions.SortKey', $sortKey);
        }
    }

    /**
     * The user preference for filtering is linked to the category. Saves the category preference for filtering on the user.
     * The category preference is usually based on the last filter the user made on the category.
     *
     * @param $filterKey The preferred filter key.
     * @param $categoryID The ID of the category associated with the filter.
     */
    protected static function setUserFilterPreferences($filterKey, $categoryID) {
        if (Gdn::session()->isValid()) {
            Gdn::userModel()->SavePreference(Gdn::session()->UserID, 'Category.'.$categoryID.'.FilterKey', $filterKey);
        }
    }

    /**
     * Get the current sort/filter query string by passing no parameters or pass either a new filter key or sort key
     * to build a new query string, leaving the other property intact.
     *
     * @param string $filterKey The key name of the filter in the filters array.
     * @param string $sortKey The key name of the sort in the sorts array.
     * @return string The current or amended query string for sort and filter.
     */
    public static function sortFilterQueryString($filterKey = '', $sortKey = '') {
        if (!$filterKey) {
            $filterKey = Gdn::request()->get('filter');
        }
        if (!$sortKey) {
            $sortKey = Gdn::request()->get('sort');
        }
        $queryString = '';
        if ($filterKey && $sortKey) {
            $queryString = '?filter='.$filterKey.'&sort='.$sortKey;
        } elseif ($filterKey) {
            $queryString = '?filter='.$filterKey;
        } elseif ($sortKey) {
            $queryString = '?sort=' . $sortKey;
        }
        return $queryString;
    }

    /**
     * Returns the current path without any page indicator. Useful for resetting sorting/filtering no matter
     * which page the user is on.
     *
     * @return string The path of the request without the page.
     */
    protected static function getPagelessPath() {
        // Remove page indicator.
        return preg_replace('/\/p\d$/i', '', Gdn::request()->path());
    }

    /**
     * Add a sort to the sorts array.
     *
     * @param string $key The key name of the sort. Appears in the query string, should be url-friendly.
     * @param string $name The display name of the sort.
     * @param string|array $orderBy An array indicating order by fields and their directions in the format:
     *      array('field1' => 'direction', 'field2' => 'direction')
     */
    public static function addSort($key, $name, $orderBy) {
        self::$sorts[$key] = array('key' => $key, 'name' => $name, 'orderBy' => $orderBy);
    }

    /**
     * Add a filter to the filters array.
     *
     * @param string $key The key name of the filter. Appears in the query string, should be url-friendly.
     * @param string $name The display name of the filter. Appears as an option in the dropdown menu.
     * @param array $wheres The where array query to execute for the filter. Uses
     * @param string $group The dropdown module will group together any items with the same group name.
     */
    public static function addFilter($key, $name, $wheres, $group = '') {
        if (!self::getFilters()) {
            // Add a way to let users clear any filters they've added.
            self::addClearFilter();
        }
        self::$filters[$key] = array('key' => $key, 'name' => $name, 'wheres' => $wheres);
        if ($group) {
            self::$filters[$key]['group'] = $group;
        }
    }

    /**
     * If you don't want to use any of the default sorts, use this little buddy.
     */
    public static function clearSorts() {
        self::$sorts = array();
    }

    /**
     * Removes a sort from the sort array with the passed key.
     *
     * @param string $key The key of the sort to remove.
     */
    public static function removeSort($key) {
        if (val($key, self::$sorts)) {
            unset(self::$sorts[$key]);
        }
    }

    /**
     * Removes a filter from the filter array with the passed key.
     *
     * @param string $key The key of the filter to remove.
     */
    public static function removeFilter($key) {
        if (val($key, self::$filters)) {
            unset(self::$filters[$key]);
        }
    }

    /**
     * Adds an option to the filters array to clear any existing filters on the data.
     */
    protected static function addClearFilter() {
        self::$filters['none'] = array('key' => 'none', 'name' => sprintf(t('All %s'), t('Discussions')), 'wheres' => array(), 'group' => 'default');
    }
}
