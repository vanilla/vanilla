<?php

/**
 * A trait for a sortable list.
 *
 * @author Becky Van Bussel <becky@vanillaforums.com>
 * @copyright 2015 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @since 2.3
 */

trait NestedCollection {

    /**
     * @var string The css class to add to active items and groups.
     */
    private $activeCssClass = 'active';

    /**
     * @var array List of items to sort.
     */
    private $items = [];

    /**
     * @var int Index number to start the item* key-generation with.
     */
    private $keyNumber = 1;

    /**
     * @var bool Whether to use CSS prefixes on the generated CSS classes for the items.
     */
    private $useCssPrefix = false;

    /**
     * @var string CSS prefix for a header item.
     */
    private $headerCssClassPrefix = 'header';

    /**
     * @var string CSS prefix for a link item.
     */
    private $linkCssClassPrefix = 'link';

    /**
     * @var string CSS prefix for a divider item.
     */
    private $dividerCssClassPrefix = 'divider';

    /**
     * @var bool Whether to flatten the list (as with a dropdown menu) or allow nesting (as with a nav).
     */
    private $flatten = false;

    /**
     * @var bool Whether to separate groups with a hr element. Only supported for flattened lists.
     */
    private $forceDivider = true;

    /**
     * @var array The allowed keys in the $modifiers array parameter in the 'addItem' methods.
     */
    private $isPrepared = false;

    /**
     * @var string The url to the display as active.
     */
    private $highlightRoute = '';

    /**
     * @var array The item modifiers allowed to be passed in the modifiers array.
     */
    private $allowedItemModifiers = ['popinRel', 'icon', 'badge', 'rel', 'description', 'attributes', 'listItemCssClasses'];

    /**
     * @param boolean $forceDivider Whether to separate groups with a <hr> element. Only supported for flattened lists.
     * @return $this
     */
    public function setForceDivider($forceDivider) {
        $this->forceDivider = $forceDivider;
        return $this;
    }

    /**
     * @return string
     */
    public function getActiveCssClass() {
        return $this->activeCssClass;
    }

    /**
     * @param string $activeCssClass
     * @return $this
     */
    public function setActiveCssClass($activeCssClass) {
        $this->activeCssClass = $activeCssClass;
        return $this;
    }

    /**
     * @param boolean $useCssPrefix
     * @return $this
     */
    public function useCssPrefix($useCssPrefix) {
        $this->useCssPrefix = $useCssPrefix;
        return $this;
    }

    /**
     * @return string
     */
    public function getHeaderCssClassPrefix() {
        return $this->headerCssClassPrefix;
    }

    /**
     * @param string $headerCssClassPrefix
     * @return $this
     */
    public function setHeaderCssClassPrefix($headerCssClassPrefix) {
        $this->headerCssClassPrefix = $headerCssClassPrefix;
        return $this;
    }

    /**
     * @return string
     */
    public function getLinkCssClassPrefix() {
        return $this->linkCssClassPrefix;
    }

    /**
     * @param string $linkCssClassPrefix
     * @return $this
     */
    public function setLinkCssClassPrefix($linkCssClassPrefix) {
        $this->linkCssClassPrefix = $linkCssClassPrefix;
        return $this;
    }

    /**
     * @return string
     */
    public function getDividerCssClassPrefix() {
        return $this->dividerCssClassPrefix;
    }

    /**
     * @param string $dividerCssClassPrefix
     * @return $this
     */
    public function setDividerCssClassPrefix($dividerCssClassPrefix) {
        $this->dividerCssClassPrefix = $dividerCssClassPrefix;
        return $this;
    }

    /**
     * @param boolean $flatten
     * @return $this
     */
    public function setFlatten($flatten) {
        $this->flatten = $flatten;
        return $this;
    }

    /**
     * @return string
     */
    public function getHighlightRoute() {
        return $this->highlightRoute;
    }

    /**
     * @param string $highlightRoute
     * @return $this
     */
    public function setHighlightRoute($highlightRoute) {
        $this->highlightRoute = $highlightRoute;
        return $this;
    }

    /**
     * @return array
     */
    public function getItems() {
        return $this->items;
    }

    /**
     * @param array $items
     * @return $this
     */
    public function setItems($items) {
        $this->items = $items;
        return $this;
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
    public function addDividerIf($isAllowed = true, $key = '', $cssClass = '', $sort = []) {
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
    public function addDivider($key = '', $cssClass = '', $sort = []) {
        $divider = ['key' => $key];
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
    public function addGroupIf($isAllowed = true, $text = '', $key = '', $cssClass = '', $sort = [], $modifiers = []) {
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
     * @return SortableModule $this The calling object.
     * @throws Exception
     */
    public function addGroup($text = '', $key = '', $cssClass = '', $sort = [], $modifiers = []) {
        $group = [
            'text' => $text,
            'key' => $key,
            'cssClass' => $cssClass
        ];

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
    public function addLinkIf($isAllowed = true, $text, $url, $key = '', $cssClass = '', $sort = [], $modifiers = [], $disabled = false) {
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
     * - **listItemCssClasses**: array - Array of class names to be applied to the list item.
     * @param bool $disabled Whether to disable the link.
     * @return $this The calling object.
     * @throws Exception
     */
    public function addLink($text, $url, $key = '', $cssClass = '', $sort = [], $modifiers = [], $disabled = false) {
        $link = [
            'text' => $text,
            'url' => $url,
            'key' => $key,
        ];

        if ($sort) {
            $link['sort'] = $sort;
        }

        if (!empty($modifiers)) {
            $this->addItemModifiers($link, $modifiers);
        }

        $this->touchKey($link);
        $link['cssClass'] = $cssClass.' '.$this->buildCssClass($this->linkCssClassPrefix, $link);

        $listItemCssClasses = val('listItemCssClasses', $modifiers, []);
        if ($disabled) {
            $listItemCssClasses[] = 'disabled';
        }
        if ($this->isActive($link)) {
            $link['isActive'] = true;
            $listItemCssClasses[] = $this->activeCssClass;
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
    private function addItemModifiers(&$item, $modifiers) {
        $modifiers = array_intersect_key($modifiers, array_flip($this->allowedItemModifiers));
        foreach ($modifiers as $attribute => $value) {
            $item[$attribute] = $value;
        }
    }

    /**
     * Generate a key for an item if one does not exist, and add the property to the item.
     *
     * @param array $item The item to generate and add a key for.
     */
    private function touchKey(&$item) {
        if (!val('key', $item)) {
            $item['key'] = 'item'.$this->keyNumber;
            $this->keyNumber = $this->keyNumber + 1;
        }
    }

    /**
     * Add an item to the items array.
     *
     * @param string $type The type of the item: link, group or divider.
     * @param array $item The item to add to the array.
     * @throws Exception
     */
    private function addItem($type, $item) {
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
                    $items[$key_part] = ['type' => 'group', 'text' => '', 'items' => [], '_sort' => count($items)];
                } elseif ($items[$key_part]['type'] !== 'group') {
                    throw new \Exception("$key_part is not a group", 500);
                } elseif (!array_key_exists('items', $items[$key_part])) {
                    // Lazy create the items array.
                    $items[$key_part]['items'] = [];
                }
                $items =& $items[$key_part]['items'];
            }
        }
    }

    /**
     * Remove an item from the nested set.
     *
     * @param string $key The key of the item to remove, separated by dots.
     */
    public function removeItem($key) {
        $parts = explode('.', $key);

        $arr = &$this->items;
        foreach ($parts as $i => $part) {
            if (array_key_exists($part, $arr)) {
                if ($i + 1 === count($parts)) {
                    unset($arr[$part]);
                } else {
                    $arr = &$arr[$part];
                }
            } else {
                // The key wasn't found so short circuit.
                return;
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
    private function buildCssClass($prefix, $item) {
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
    private function isActive($item) {
        if (empty($this->highlightRoute)) {
            $highlightRoute = Gdn_Url::request(true);
        } else {
            $highlightRoute = url($this->highlightRoute);
        }
        return (val('url', $item) && (trim(url(val('url', $item)), '/') == trim($highlightRoute, '/')));
    }

    /**
     * Recursive function to sort the items in a given array.
     *
     * @param array $items The items to sort.
     */
    private function sortItems(&$items) {
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
    private function sortItemsOrder($item, $items, $depth = 0) {
        $default_sort = val('_sort', $item, 100);

        // Check to see if a custom sort has been specified.
        if (isset($item['sort'])) {
            if (is_numeric($item['sort'])) {
                // This is a numeric sort
                return $item['sort'] * 10000 + $default_sort;
            } elseif (is_array($item['sort']) && $depth < 10) {
                // This sort is before or after another depth.
                $op = array_keys($item['sort'])[0];
                $key = $item['sort'][$op];

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
        $this->prepareData($this->items);
        if ($this->flatten) {
            $this->items = $this->flattenArray($this->items);
        }
        return !empty($this->items);
    }

    /**
     * Performs post-sort operations to the items array.
     * Removes empty groups, removes the '_sort' and 'key' attributes and bubbles up the active css class.
     *
     * @param array $items The item list to parse.
     */
    private function prepareData(&$items) {
        foreach($items as $key => &$item) {
            unset($item['_sort'], $item['key']);
            $subItems = false;

            // Group item
            if (val('type', $item) === 'group') {
                // ensure groups have items
                if (val('items', $item)) {
                    $subItems = true;
                } else {
                    unset($items[$key]);
                }
            }

            if ($subItems) {
                $this->prepareData($item['items']);
                // Set active state on parents if child has it
                if (!$this->flatten) {
                    foreach ($item['items'] as $subItem) {
                        if (val('isActive', $subItem)) {
                            $item['isActive'] = true;
                            $item['cssClass'] .= ' '.$this->activeCssClass;
                        }
                    }
                }
            }
        }
    }

    /**
     * Recursive utility function to support returning this object as an array.
     *
     * @param $obj The object to transform.
     * @param array $blackList Blacklisted property names.
     * @param array $whiteList Whitelisted property names. If set, only whitelisted properties will appear in the result.
     * @return array An array transformation of this object.
     */
    private function objectToArray($obj, array $blackList = [], array $whiteList = []) {
        if (is_array($obj) || is_object($obj)) {
            $result = [];
            foreach ($obj as $key => $value) {
                if (!in_array($key, $blackList) && (empty($whiteList) || in_array($key, $whiteList))) {
                    $result[$key] = $this->objectToArray($value);
                }
            }
            return $result;
        }
        return $obj;
    }

    /**
     * Copies the object to an array. A simple (array) typecast won't work,
     * since the properties are private and as such, add unwanted information to the array keys.
     *
     * @param array $blackList Blacklisted property names.
     * @param array $whiteList Whitelisted property names. If set, only whitelisted properties will appear in the result.
     * @return array Copy of this object in an array format.
     */
    public function toArray(array $blackList = [], array $whiteList = []) {
        $blackList[] = '_Sender';
        return $this->objectToArray($this, $blackList, $whiteList);
    }

    /**
     * Creates a flattened array of menu items.
     * Useful for lists like dropdown menu, where nesting lists is not necessary.
     *
     * @param array $items The item list to flatten.
     * @return array The flattened items list.
     */
    private function flattenArray($items) {
        $newItems = [];
        $itemslength = sizeof($items);
        $index = 0;
        foreach($items as $key => $item) {
            $subItems = [];

            // Group item
            if (val('type', $item) == 'group') {
                if (val('items', $item)) {
                    $subItems = $item['items'];
                    unset($item['items']);
                    if (val('text', $item)) {
                        $newItems[] = $item;
                    }
                }
            }
            if ((val('type', $item) != 'group')) {
                $newItems[] = $item;
            }
            if ($subItems) {
                $newItems = array_merge($newItems, $this->flattenArray($subItems));
                if ($this->forceDivider && $index + 1 < $itemslength) {
                    // Add hr after group but not the last one
                    $newItems[] = ['type' => 'divider'];
                }
            }
            $index++;
        }
        return $newItems;
    }
}
