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

    protected $sorts;

    protected $filters;

    public function __construct() {}

    /**
     * Checks whether we should even render this whole thing.
     *
     * @return bool Whether to render the module.
     */
    public function prepare() {
        $this->sorts = DiscussionModel::getSorts();
        $this->filters = DiscussionModel::getFilters();
        return !empty($this->sorts) || !empty($this->filters);
    }

    /**
     * Returns an array of sanitized sort data for the view.
     * (Data uses no rendering module and must be manually inserted into the view.)
     *
     * @return array An array of sorts consisting of the name, url, rel and cssClass of each sort item.
     */
    protected function getSortData() {
        $sortData = array();
        foreach($this->sorts as $sort) {
            $key = val('key', $sort);
            $sortData[$key]['name'] = val('name', $sort);
            $sortData[$key]['url'] = $this->getPagelessPath().DiscussionModel::sortFilterQueryString([], $key);
            $sortData[$key]['rel'] = 'nofollow';
            if (DiscussionModel::getSortKeySelected() == val('key', $sort)) {
                $sortData[$key]['cssClass'] = self::ACTIVE_CSS_CLASS;
            }
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
        $dropdowns = [];
        foreach($this->filters as $filterSet) {
            $setKey = val('key', $filterSet);
            $dropdown = new DropdownModule('discussions-filter-'.$setKey, val('name', $filterSet), 'discussion-filter');

            // Override the trigger text?
            $selectedFilterKeys = DiscussionModel::getFilterKeySelected();
            $selectedValue = val($setKey, $selectedFilterKeys);
            if ($selectedValue && $selectedValue != 'none') {
                $selected = val('name', $filterSet['filters'][$selectedValue]);
                $dropdown->setTrigger($selected);
            }

            $dropdown->setView('dropdown-navbutton'); // TODO make this a property?
            $dropdown->setForceDivider(true); // Adds dividers between groups in the dropdown.

            // Add the filters to the dropdown
            foreach (val('filters', $filterSet) as $filter) {
                $key = val('group', $filter, '') . '.' . val('key', $filter);
                $dropdown->addLink(
                    val('name', $filter),
                    url($this->getPagelessPath().DiscussionModel::sortFilterQueryString([$setKey => val('key', $filter)])),
                    $key,
                    '', array(), false,
                    array('rel' => 'nofollow')
                );
            }
            $dropdowns[] = $dropdown;
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
