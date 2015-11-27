<?php if (!defined('APPLICATION')) exit();

/**
 * A module for a nav.
 *
 * @author Becky Van Bussel <becky@vanillaforums.com>
 * @copyright 2015 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @since 2.2
 */

/**
 * A module for a list of links.
 */
class NavModule extends SortableModule {

    /**
     * @var string A potential CSS class of the dropdown menu wrapper container.
     */
    public $cssClass;

    /**
     * @var string A potential CSS class of the list <ul> block.
     */
    public $listCssClass = '';


    public function __construct($cssClass = '', $useCssPrefix = true) {
	parent::__construct(false, $useCssPrefix);

	// Set parent attributes
	$this->cssClass = $cssClass;

	if ($useCssPrefix) {
	    $this->headerCssClassPrefix = 'nav-header';
	    $this->linkCssClassPrefix = 'nav-link';
	    $this->dropdownCssClassPrefix = 'nav-dropdown';
	    $this->dividerCssClassPrefix = 'divider';
        }
    }

    public function addDropdown($dropdown, $key = '', $cssClass = '', $sort = array()) {
	if (!is_a($dropdown, 'DropdownModule')) {
	    // error
        }
	$dropdownItem['type'] = 'dropdown';
	if ($key) {
	    $dropdownItem['key'] = $key;
        }
	if ($sort) {
	    $dropdownItem['sort'] = $sort;
        }
	$dropdown['cssClass'] = $cssClass.' '.$this->buildCssClass($this->linkCssClassPrefix, $dropdown);
	$dropdown->tag = 'li';
	$dropdown->prepare();
	$dropdownItem['dropdownmenu'] = $dropdown;
	$this->addItem('dropdown', $dropdownItem);
	return $this;
    }
}
