<?php
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
 */
class NavModule extends Gdn_Module {

    use NestedCollection;

    /**
     * @var string CSS class for the nav wrapper container.
     */
    private $cssClass;


    /**
     * @var string CSS prefix for a dropdown item.
     */
    private $dropdownCssClassPrefix = 'nav-dropdown';

    /**
     *
     *
     * @param string $cssClass A potential CSS class of the dropdown menu wrapper container.
     * @param bool $useCssPrefix Whether to use CSS prefixes on the nav items.
     */
    public function __construct($cssClass = '', $useCssPrefix = true) {
        parent::__construct();

        $this->setFlatten(false);
        $this->useCssPrefix($useCssPrefix);
        $this->cssClass = $cssClass;

        if ($useCssPrefix) {
            $this->setHeaderCssClassPrefix('nav-header');
            $this->setLinkCssClassPrefix('nav-link');
            $this->setDividerCssClassPrefix('divider');
        }
    }

    /**
     * @return string CSS prefix for a dropdown item.
     */
    public function getDropdownCssClassPrefix() {
        return $this->dropdownCssClassPrefix;
    }

    /**
     * @param string $dropdownCssClassPrefix CSS prefix for a dropdown item.
     * @return NavModule $this The calling object.
     */
    public function setDropdownCssClassPrefix($dropdownCssClassPrefix) {
        $this->dropdownCssClassPrefix = $dropdownCssClassPrefix;
        return $this;
    }

    /**
     * @return string CSS class for the nav wrapper container.
     */
    public function getCssClass() {
        return $this->cssClass;
    }

    /**
     * @param string $cssClass CSS class for the nav wrapper container.
     * @return NavModule $this The calling object.
     */
    public function setCssClass($cssClass) {
        $this->cssClass = $cssClass;
        return $this;
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
    public function addDropdownIf($isAllowed = true, $dropdown, $key = '', $cssClass = '', $sort = []) {
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
    public function addDropdown($dropdown, $key = '', $cssClass = '', $sort = []) {
        if (is_a($dropdown, 'DropdownModule')) {
            $dropdown->setTag('li');
            $dropdown->prepare();
            $dropdownItem = ['type' => 'dropdown'];
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

    /**
     * @return string
     * @throws Exception
     */
    public function toString() {
        $this->fireAs(get_called_class())->fireEvent('render');
        return parent::toString();
    }

    /**
     * Convert some text to a key.
     *
     * @param $text
     * @return string
     */
    public static function textToKey($text) {
        $text = strip_tags($text);
        $text = strtolower(trim($text));
        $text = str_replace(' ', '-', $text);
        $text = preg_replace('/-+/', '-', $text);
        return $text;
    }
}
