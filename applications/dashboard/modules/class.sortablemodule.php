<?php

/**
 * A module for a sortable list.
 *
 * @author Becky Van Bussel <becky@vanillaforums.com>
 * @copyright 2015 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @since 2.3
 */

abstract class SortableModule extends Gdn_Module {

    /**
     * @var string The css class to add to active items and groups.
     */
    const ACTIVE_CSS_CLASS = 'Active';

    /**
     * @var array List of items to sort.
     */
    public $items = array();

    /**
     * @var int Index number to start the item* key-generation with.
     */
    private $keyNumber = 1;

    /**
     * @var bool Whether to use CSS prefixes on the generated CSS classes for the items.
     */
    public $useCssPrefix = false;

    /**
     * @var string CSS prefix for a header item.
     */
    public $headerCssClassPrefix = 'header';

    /**
     * @var string CSS prefix for a link item.
     */
    public $linkCssClassPrefix = 'link';

    /**
     * @var string CSS prefix for a divider item.
     */
    public $dividerCssClassPrefix = 'divider';

    /**
     * @var bool Whether to flatten the list (as with a dropdown menu) or allow nesting (as with a nav).
     */
    private $flatten;

    /**
     * @var bool Whether to separate groups with a hr element. Only supported for flattened lists.
     */
    private $forceDivider = false;

    /**
     * @var bool Whether we have run the prepare method yet.
     */
    private $isPrepared = false;

    private $allowedItemModifiers = array('popinRel', 'icon', 'badge');

    /**
     * Constructor. Should be called by all extending classes' constructors.
     *
     * @param string $view The filename of the view to render, excluding the extension.
     * @param bool $flatten Whether to flatten the list (as with a dropdown menu) or allow nesting (as with a nav).
     * @param bool $useCssPrefix Whether to use CSS prefixes on the generated CSS classes for the items.
     */
    public function __construct($flatten, $useCssPrefix = false) {
	parent::__construct();
        $this->flatten = $flatten;
        $this->useCssPrefix = $useCssPrefix;
    }

    /**
     * @param boolean $forceDivider Whether to separate groups with a <hr> element. Only supported for flattened lists.
     */
    public function setForceDivider($forceDivider) {
        $this->forceDivider = $forceDivider;
    }

    /**
     * Add a divider to the items array if it satisfies the $isAllowed condition.
     *
     * @param bool|string|array $isAllowed Either a boolean to indicate whether to actually add the item
     * or a permission string or array of permission strings (full match) to check.
     * @param string $key The item's key (for sorting and CSS targeting).
     * @param array|int $sort Either a numeric sort position or and array in the style: array('before|after', 'key').
     * @param string $cssClass The divider's CSS class.
     * @return object $this The calling object.
     * @throws Exception
     */
    public function addDividerIf($isAllowed = true, $key = '', $cssClass = '', $sort = array()) {
	if (!$this->isAllowed($isAllowed)) {
            return $this;
        } else {
            return $this->addDivider($key, $cssClass, $sort);
        }
    }

    /**
     * Add a divider to the items array.
     *
     * @param string $key The item's key (for sorting and CSS targeting).
     * @param array|int $sort Either a numeric sort position or and array in the style: array('before|after', 'key').
     * @param string $cssClass The divider's CSS class.
     * @return object $this The calling object.
     * @throws Exception
     */
    public function addDivider($key = '', $cssClass = '', $sort = array()) {
        $divider['key'] = $key;
        if ($sort) {
            $divider['sort'] = $sort;
        }

        $this->touchKey($divider);
        $divider['cssClass'] = $cssClass.' '.$this->buildCssClass($this->dividerCssClassPrefix, $divider);

        $this->addItem('divider', $divider);
        return $this;
    }

    /**
     * Add a group to the items array if it satisfies the $isAllowed condition.
     *
     * @param bool|string|array $isAllowed Either a boolean to indicate whether to actually add the item
     * or a permission string or array of permission strings (full match) to check.
     * @param string $text The display text for the group header.
     * @param string $key The item's key (for sorting and CSS targeting).
     * @param string $cssClass The group header's CSS class.
     * @param array|int $sort Either a numeric sort position or and array in the style: array('before|after', 'key').
     * @param array $modifiers List of attribute => value, where the attribute is in $this->allowedItemModifiers.
     * - **popinRel**: string - Endpoint for a popin.
     * - **badge**: string - Info to put into a badge, usually a number.
     * - **icon**: string - Name of the icon for the item, excluding the 'icon-' prefix.
     * @return object $this The calling object.
     * @throws Exception
     */
    public function addGroupIf($isAllowed = true, $text = '', $key = '', $cssClass = '', $sort = array(), $modifiers = array()) {
	if (!$this->isAllowed($isAllowed)) {
            return $this;
        } else {
            return $this->addGroup($text, $key, $cssClass, $sort, $modifiers);
        }
    }


    /**
     * Checks whether an item can be added to the items list by returning it if it is already a boolean,
     * or checking the permission if it is a string or array.
     *
     * @param bool|string|array $isAllowed Either a boolean to indicate whether to actually add the item
     * or a permission string or array of permission strings (full match) to check.
     * @return bool Whether the item has permission to be added to the items list.
     */
    protected function isAllowed($isAllowed) {
        if (is_bool($isAllowed)) {
            return $isAllowed;
        }
        if (is_string($isAllowed) || is_array($isAllowed)) {
            return Gdn::session()->checkPermission($isAllowed);
        }
        return false;
    }

    /**
     * Add a group to the items array.
     *
     * @param string $text The display text for the group header.
     * @param string $key The item's key (for sorting and CSS targeting).
     * @param string $cssClass The group header's CSS class.
     * @param array|int $sort Either a numeric sort position or and array in the style: array('before|after', 'key').
     * @param array $modifiers List of attribute => value, where the attribute is in $this->allowedItemModifiers.
     * - **popinRel**: string - Endpoint for a popin.
     * - **badge**: string - Info to put into a badge, usually a number.
     * - **icon**: string - Name of the icon for the item, excluding the 'icon-' prefix.
     * @return object $this The calling object.
     * @throws Exception
     */
    public function addGroup($text = '', $key = '', $cssClass = '', $sort = array(), $modifiers = array()) {
        $group = array(
            'text' => $text,
            'key' => $key,
            'cssClass' => $cssClass
        );

        if ($sort) {
            $group['sort'] = $sort;
        }

        if (!empty($modifiers)) {
            $this->addItemModifiers($group, $modifiers);
        }

        $this->touchKey($group);

        if ($text) {
            $group['headerCssClass'] = $cssClass.' '.$this->buildCssClass($this->headerCssClassPrefix, $group);
        }
        $this->addItem('group', $group);
        return $this;
    }

    /**
     * Add a link to the items array if it satisfies the $isAllowed condition.
     *
     * @param bool|string|array $isAllowed Either a boolean to indicate whether to actually add the item
     * or a permission string or array of permission strings (full match) to check.
     * @param string $text The display text for the link.
     * @param string $url The destination url for the link.
     * @param string $key The item's key (for sorting and CSS targeting).
     * @param string $cssClass The link's CSS class.
     * @param array|int $sort Either a numeric sort position or and array in the style: array('before|after', 'key').
     * @param array $modifiers List of attribute => value, where the attribute is in $this->allowedItemModifiers.
     * - **popinRel**: string - Endpoint for a popin.
     * - **badge**: string - Info to put into a badge, usually a number.
     * - **icon**: string - Name of the icon for the item, excluding the 'icon-' prefix.
     * @param bool $disabled Whether to disable the link.
     * @return object $this The calling object.
     */
    public function addLinkIf($isAllowed = true, $text, $url, $key = '', $cssClass = '', $sort = array(), $modifiers = array(), $disabled = false) {
	if (!$this->isAllowed($isAllowed)) {
            return $this;
        } else {
	    return $this->addLink($text, $url, $key, $cssClass, $sort, $modifiers, $disabled);
        }
    }

    /**
     * Add a link to the items array.
     *
     * @param string $text The display text for the link.
     * @param string $url The destination url for the link.
     * @param string $key The item's key (for sorting and CSS targeting).
     * @param string $cssClass The link's CSS class.
     * @param array|int $sort Either a numeric sort position or and array in the style: array('before|after', 'key').
     * @param array $modifiers List of attribute => value, where the attribute is in $this->allowedItemModifiers.
     * - **popinRel**: string - Endpoint for a popin.
     * - **badge**: string - Info to put into a badge, usually a number.
     * - **icon**: string - Name of the icon for the item, excluding the 'icon-' prefix.
     * @param bool $disabled Whether to disable the link.
     * @return object $this The calling object.
     * @throws Exception
     */
    public function addLink($text, $url, $key = '', $cssClass = '', $sort = array(), $modifiers = array(), $disabled = false) {
        $link = array(
            'text' => $text,
	    'url' => url($url),
            'key' => $key,
        );

        if ($sort) {
            $link['sort'] = $sort;
        }

        if (!empty($modifiers)) {
            $this->addItemModifiers($link, $modifiers);
        }

        $this->touchKey($link);
        $link['cssClass'] = $cssClass.' '.$this->buildCssClass($this->linkCssClassPrefix, $link);

        $listItemCssClasses = array();
        if ($disabled) {
            $listItemCssClasses[] = 'disabled';
        }
        if ($this->isActive($link)) {
	    $link['isActive'] = true;
            $listItemCssClasses[] = SortableModule::ACTIVE_CSS_CLASS;
        } else {
	    $link['isActive'] = false;
        }

        $link['listItemCssClass'] = implode(' ', $listItemCssClasses);

	    $this->addItem('link', $link);
        return $this;
    }

    /**
     * Adds the attributes in the modifiers array to the item.
     * Constrains the modifier to those defined in $this->allowedItemModifiers.
     *
     * @param array $item The item to modify.
     * @param array $modifiers The modifiers to add to the item.
     */
    public function addItemModifiers(&$item, $modifiers) {
        $modifiers = array_intersect_key($modifiers, array_flip($this->allowedItemModifiers));
        foreach($modifiers as $attribute => $value) {
            $item[$attribute] = $value;
        }
    }

    /**
     * Generate a key for an item if one does not exist, and add the property to the item.
     *
     * @param array $item The item to generate and add a key for.
     */
    protected function touchKey(&$item) {
        if (!val('key', $item)) {
            $item['key'] = 'item'.$this->keyNumber;
            $this->keyNumber = $this->keyNumber+1;
        }
    }

    /**
     * Add an item to the items array.
     *
     * @param string $type The type of the item: link, group or divider.
     * @param array $item The item to add to the array.
     * @throws Exception
     */
    protected function addItem($type, $item) {
        $this->touchKey($item);
        if (!is_array(val('key', $item))) {
            $item['key'] = explode('.', val('key', $item));
        } else {
            $item['key'] = array_values(val('key', $item));
        }

        $item = (array)$item;

        // Make sure the link has its type.
        $item['type'] = $type;

        // Walk into the items list to set the item.
        $items =& $this->items;
        foreach (val('key', $item) as $i => $key_part) {

            if ($i === count(val('key', $item)) - 1) {
                // Add the item here.
                if (array_key_exists($key_part, $items)) {
                    // The item is already here so merge this one on top of it.
                    if ($items[$key_part]['type'] !== $type)
                        throw new \Exception(val('key', $item)." of type $type does not match existing type {$items[$key_part]['type']}.", 500);

                    $items[$key_part] = array_merge($items[$key_part], $item);
                } else {
                    // The item is new so just add it here.
                    touchValue('_sort', $item, count($items));
                    $items[$key_part] = $item;
                }
            } else {
                // This is a group.
                if (!array_key_exists($key_part, $items)) {
                    // The group doesn't exist so lazy-create it.
                    $items[$key_part] = array('type' => 'group', 'text' => '', 'items' => array(), '_sort' => count($items));
                } elseif ($items[$key_part]['type'] !== 'group') {
                    throw new \Exception("$key_part is not a group", 500);
                } elseif (!array_key_exists('items', $items[$key_part])) {
                    // Lazy create the items array.
                    $items[$key_part]['items'] = array();
                }
                $items =& $items[$key_part]['items'];
            }
        }
    }

    /**
     * Builds a CSS class for an item, based on the 'key' property of the item.
     * Optionally prepends a prefix to generated class names.
     *
     * @param string $prefix The optional prefix to add to class name.
     * @param array $item The item to generate CSS class for.
     * @return string The generated CSS class.
     */
    protected function buildCssClass($prefix, $item) {
        $result = '';
        if ($prefix) {
            $prefix .= '-';
        }
        if (!$this->useCssPrefix) {
            $prefix = '';
        }
        if (val('key', $item)) {
            if (is_array(val('key', $item))) {
                $result .= $prefix.implode('-', val('key', $item));
            }
            else {
                $result .= $prefix.str_replace('.', '-', val('key', $item));
            }
        }
        return trim($result);
    }

    /**
     * Checks whether the current request url matches an item's link url.
     *
     * @param array $item The item to check.
     * @return bool Whether the current request url matches an item's link url.
     */
    protected function isActive($item) {
	$highlightRoute = Gdn_Url::request(true);
	return (val('url', $item) && (trim(val('url', $item), '/') == trim($highlightRoute, '/')));
    }

    /**
     * Recursive function to sort the items in a given array.
     *
     * @param array $items The items to sort.
     */
    protected function sortItems(&$items) {
        foreach($items as &$item) {
            if (val('items', $item)) {
                $this->sortItems($item['items']);
            }
        }
        uasort($items, function($a, $b) use ($items) {
            $sort_a = $this->sortItemsOrder($a, $items);
            $sort_b = $this->sortItemsOrder($b, $items);

            if ($sort_a > $sort_b)
                return 1;
            elseif ($sort_a < $sort_b)
                return -1;
            else
                return 0;
        });
    }

    /**
     * Get the sort order of an item in the items array.
     * This function looks at the following keys:
     * - **sort (numeric)**: A specific numeric sort was provided.
     * - **sort array('before|after', 'key')**: You can specify that the item is before or after another item.
     * - **_sort**: The order the item was added is used.
     *
     * @param array $item The item to get the sort order from.
     * @param array $items The entire list of items.
     * @param int $depth The current recursive depth used to prevent infinite recursion.
     * @return number
     */
    protected function sortItemsOrder($item, $items, $depth = 0) {
        $default_sort = val('_sort', $item, 100);

        // Check to see if a custom sort has been specified.
        if (isset($item['sort'])) {
            if (is_numeric($item['sort'])) {
                // This is a numeric sort
                return $item['sort'] * 10000 + $default_sort;
            } elseif (is_array($item['sort']) && $depth < 10) {
                // This sort is before or after another depth.
                list($op, $key) = $item['sort'];

                if (array_key_exists($key, $items)) {
                    switch ($op) {
                        case 'after':
                            return $this->sortItemsOrder($items[$key], $items, $depth + 1) + 1000;
                        case 'before':
                        default:
                            return $this->sortItemsOrder($items[$key], $items, $depth + 1) - 1000;
                    }
                }
            }
        }
        return $default_sort * 10000 + $default_sort;
    }

    /**
     * Prepares the items array for output by sorting and optionally flattening.
     *
     * @return bool Whether to render the module.
     */
    public function prepare() {
        if ($this->isPrepared) {
            return !empty($this->items);
        }
        $this->isPrepared = true;
        $this->sortItems($this->items);
	$this->cleanData($this->items);
        if ($this->flatten) {
            $this->items = $this->flattenArray($this->items);
        }
        return !empty($this->items);
    }

    /**
     * Removes empty groups, removes the '_sort' and 'key' attributes,
     * .
     *
     * @param array $items The item list to clean.
     */
    protected function cleanData(&$items) {
	foreach($items as $key => &$item) {
            unset($item['_sort'], $item['key']);
            $subitems = false;

            // Group item
            if (val('type', $item) == 'group') {
                // ensure groups have items
                if (val('items', $item)) {
                    $subitems = $item['items'];
                } else {
                    unset($items[$key]);
                }
            }
            if ($subitems) {
                $this->cleanData($subitems);
		// Set active state on parents if child has it
		if (!$this->flatten) {
		    foreach ($subitems as $subitem) {
			if (val('isActive', $subitem)) {
			    $item['isActive'] = true;
			    $item['cssClass'] .= ' '.SortableModule::ACTIVE_CSS_CLASS;
			}
		    }
		}
	    }
	    }
    }

    /**
     * Creates a flattened array of menu items.
     * Useful for lists like dropdown menu, where nesting lists is not necessary.
     *
     * @param array $items The item list to flatten.
     * @return array The flattened items list.
     */
    protected function flattenArray($items) {
        $newitems = array();
        $itemslength = sizeof($items);
        $index = 0;
        foreach($items as $key => $item) {
            $subitems = false;

            // Group item
            if (val('type', $item) == 'group') {
                if (val('items', $item)) {
                    $subitems = $item['items'];
                    unset($item['items']);
                    if (val('text', $item)) {
                        $newitems[] = $item;
                    }
                }
            }
            if ((val('type', $item) != 'group')) {
                $newitems[] = $item;
            }
            if ($subitems) {
                $newitems = array_merge($newitems, $this->flattenArray($subitems));
                if ($this->forceDivider && $index < $itemslength) {
                    // Add hr after group but not the last one
                    $newitems[] = array('type' => 'divider');
                }
            }
        }
        return $newitems;
    }
}
