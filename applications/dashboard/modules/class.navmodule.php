<?php if (!defined('APPLICATION')) exit();
use NestedCollection;

/**
 * A nav menu module.
 *
 * @author Becky Van Bussel <becky@vanillaforums.com>
 * @copyright 2015 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @since 2.2
 */

/**
 * A flawlessly configurable nav menu module.
 *
 * The contains nestable menu items. Menu items can be
 *
 * **link**     - An link item.
 * **group**    - A group item to create a logical grouping of menu items
 *                for sorting purposes, and/or to create a heading.
 * **divider**  - A dividing line.
 * **dropdown** - A DropdownModule object.
 *
 * Each item must have a unique key. If not supplied, the class will generate
 * one in the format: 'item*', where * is an auto incrementing number.
 * Keys can be used for sorting purposes and for adding links to a group.
 * For example, you could set the sort property to an item to array('before'=>'key1')
 * and it would place the item before another item with the key of 'key1'.
 * If you have a group with the key of 'key2', you can add to this group by
 * setting the key of a new item to 'key2.newItemKey'.
 * The sort property can also be an integer, indicating the item's position in the menu.
 *
 *
 * Here is an example nav creation:
 *
 *
 *
 * Which results in a nav:
 *
 *
 *
 */
class NavModule extends Gdn_Module {

    use NestedCollection;

    /**
     * @var string A potential CSS class of the nav wrapper container.
     */
    public $cssClass;

    /**
     *
     *
     * @param string $cssClass A potential CSS class of the dropdown menu wrapper container.
     * @param bool $useCssPrefix Whether to use CSS prefixes on the nav items.
     */
    public function __construct($cssClass = '', $useCssPrefix = true) {
        // Don't render an empty group.
	parent::__construct();
            return;
        }

	$this->flatten = false;
	$this->useCssPrefix = $useCssPrefix;
        $this->cssClass = $cssClass;

        if ($useCssPrefix) {
            $this->headerCssClassPrefix = 'nav-header';
            $this->linkCssClassPrefix = 'nav-link';
            $this->dropdownCssClassPrefix = 'nav-dropdown';
            $this->dividerCssClassPrefix = 'divider';
        }
    }

    /**
     * Add a dropdown to the items array if it satisfies the $isAllowed condition.
     *
     * @param bool|string|array $isAllowed Either a boolean to indicate whether to actually add the item
     * or a permission string or array of permission strings (full match) to check.
     * @param DropdownModule $dropdown The dropdown menu to add.
     * @param string $key The item's key (for sorting and CSS targeting).
     * @param string $cssClass The dropdown wrapper's CSS class.
     * @param array|int $sort Either a numeric sort position or and array in the style: array('before|after', 'key').
     * @return NavModule $this The calling object.
     */
    public function addDropdownIf($isAllowed = true, $dropdown, $key = '', $cssClass = '', $sort = array()) {
	if (!$this->isAllowed($isAllowed)) {
	    return $this;
	} else {
	    return $this->addDropdown($dropdown, $key, $cssClass, $sort);
        }
    }

    /**
     * Add a dropdown menu to the items array.
     *
     * @param DropdownModule $dropdown The dropdown menu to add.
     * @param string $key The item's key (for sorting and CSS targeting).
     * @param string $cssClass The dropdown wrapper's CSS class.
     * @param array|int $sort Either a numeric sort position or and array in the style: array('before|after', 'key').
     * @return NavModule $this The calling object.
     * @throws Exception
     */
    public function addDropdown($dropdown, $key = '', $cssClass = '', $sort = array()) {
	if (is_a($dropdown, 'DropdownModule')) {
	    $dropdown->tag = 'li';
	    $dropdown->prepare();
	    $dropdownItem['type'] = 'dropdown';
	    if ($key) {
		$dropdownItem['key'] = $key;
	    }
	    if ($sort) {
		$dropdownItem['sort'] = $sort;
	    }
	    $dropdownItem['dropdownmenu'] = $dropdown;
	    $dropdownItem['cssClass'] = $cssClass.' '.$this->buildCssClass($this->dropdownCssClassPrefix, $dropdownItem);
	    $this->addItem('dropdown', $dropdownItem);
	}
        return $this;
    }
}
