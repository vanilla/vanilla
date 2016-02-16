<?php
/**
 * Discussion Sort/Filter module
 *
 * @copyright 2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Vanilla
 * @since 2.0
 */

/**
 * DiscussionSortFilterModule
 */
class DiscussionSortFilterModule extends Gdn_Module {

    const ACTIVE_CSS_CLASS = 'Active btn-default active'; // TODO: think about this.

    protected static $sorts = array(
        'hot' => array('key' => 'hot', 'name' => 'Hot', 'field' => 'd.DateLastComment', 'direction' => 'desc'),
        'top' => array('key' => 'top', 'name' => 'Top', 'field' => array('d.Score' => 'desc', 'd.DateInserted' => 'desc')),
        'new' => array('key' => 'new', 'name' => 'New', 'field' => 'd.DateInserted', 'direction' => 'desc')
    );

    protected static $filters;

    public static $sortFieldSelected;

    public static $filterFieldSelected;

    public function __construct() {}

    public static function getSorts() {
        return self::$sorts;
    }

    public static function getFilters() {
        return self::$filters;
    }

    /**
     * Sorting/filtering is only supported in Categories at this time.
     *
     * @return bool
     */
    public function canSort() {
        $controller = Gdn::controller();
        return (val('CategoryID', $controller) && (val('CountDiscussions', $controller) > 0));
    }

    protected static function buildFilterDropdown() {
        if (!self::getFilters()) {
            return null;
        }
        $dropdown = new DropdownModule('discussions-filter', t('Filter Discussions'), 'discussion-filter');
        if (self::$filterFieldSelected && self::$filterFieldSelected != 'none') {
            $selected = val('name', val(self::$filterFieldSelected, self::getFilters()));
            $dropdown->setTrigger($selected);
        }
        $dropdown->setView('dropdown-navbutton');
        $dropdown->setForceDivider(true);
        foreach(self::$filters as $filter) {
            $key = val('group', $filter, '').'.'.val('key', $filter);
            $dropdown->addLink(
                val('name', $filter),
                url(self::getPath().self::getSortFilterQueryString(val('key', $filter))),
                $key
            );
        }

        return $dropdown;
    }

    public static function renderFilterDropdown() {
        $dropdown =  self::buildFilterDropdown();
        if ($dropdown) {
            echo $dropdown;
        } else {
            echo '';
        }
    }


    public static function getSortFromRequest() {
        if ($sortCode = Gdn::request()->get('sort')) {
            $sort = val($sortCode, self::getSorts());

            // update properties
            if ($sort) {
                self::setUserSortPreferences(val('field', $sort));
                self::$sortFieldSelected = val('field', $sort);
                return $sort;
            }
        }
        return array();
    }

    /**
     *
     * @return array
     */
    public static function getFilterFromRequest() {
        if ($filterCode = Gdn::request()->get('filter')) {
            $filter = val($filterCode, self::getFilters());
            // update properties
            if ($filter) {
                // Filtering preferences are ased on category
                if ($categoryID = val('CategoryID', Gdn::controller())) {
                    self::setUserFilterPreferences(val('key', $filter), $categoryID);
                }
                self::$filterFieldSelected = val('key', $filter);
                return $filter;
            }
        }
        return array();
    }

    public static function getUserSortPreference() {
        if (Gdn::session()->isValid()) {
            return self::$sortFieldSelected = Gdn::session()->GetPreference('Discussions.SortField', 'd.DateLastComment');
        }
    }

    public static function getUserFilterPreference($categoryID) {
        if (Gdn::session()->isValid()) {
            return self::$filterFieldSelected = Gdn::session()->GetPreference('Category.'.$categoryID.'.FilterField');
        }
    }

    public static function setUserSortPreferences($sortField) {
        if (Gdn::session()->isValid()) {
            Gdn::userModel()->SavePreference(Gdn::session()->UserID, 'Discussions.SortField', $sortField);
        }
    }

    public static function setUserFilterPreferences($filterField, $categoryID) {
        if (Gdn::session()->isValid()) {
            Gdn::userModel()->SavePreference(Gdn::session()->UserID, 'Category.'.$categoryID.'.FilterField', $filterField);
        }
    }

    public static function getSortFilterQueryString($filterCode = '', $sortCode = '') {
        if (!$filterCode) {
            $filterCode = Gdn::request()->get('filter');
        }
        if (!$sortCode) {
            $sortCode = Gdn::request()->get('sort');
        }
        $queryString = '';
        if ($filterCode && $sortCode) {
            $queryString = '?filter='.$filterCode.'&sort='.$sortCode;
        } elseif ($filterCode) {
            $queryString = '?filter='.$filterCode;
        } elseif ($sortCode) {
            $queryString = '?sort=' . $sortCode;
        }
        return $queryString;
    }

    public static function getPath() {
        // remove any page indicator
        return preg_replace('/\/p\d$/i', '', Gdn::request()->path());
    }

    public static function addSort($key, $name, $field, $direction = '') {
        self::$sorts[$key] = array('key' => $key, 'name' => $name, 'field' => $field);
        if ($direction) {
            self::$sorts[$key]['direction'] = $direction;
        }
    }

    public static function addClearFilter() {
        self::$filters['none'] = array('key' => 'none', 'name' => t('Clear Filters'), 'wheres' => array(), 'group' => 'default');
    }

    public static function addFilter($key, $name, $wheres, $group = '') {
        if (!self::getFilters()) {
            self::addClearFilter();
        }
        self::$filters[$key] = array('key' => $key, 'name' => $name, 'wheres' => $wheres);
        if ($group) {
            self::$filters[$key]['group'] = $group;
        }
    }
}
