<?php if (!defined('APPLICATION')) exit();

/**
 * A module for a sortable list.
 *
 * @author Becky Van Bussel <becky@vanillaforums.com>
 * @copyright 2015 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @since 2.3
 */

abstract class SortableModule extends ComponentModule {

    public $items = array();

    protected $keyNumber = 1;

    public $useCssPrefix;

    public $headerCssClassPrefix;
    public $linkCssClassPrefix;
    public $dividerCssClassPrefix;

    private $flatten;
    private $isRendered = false;

    /// Methods ///

    public function __construct($view, $flatten, $useCssPrefix = false) {
        parent::__construct($view);
//        $this->_ApplicationFolder = 'dashboard';
        $this->flatten = $flatten;
        $this->useCssPrefix = $useCssPrefix;
    }

    /**
     * Add a divider to the items array.
     *
     * @param string $key The key of the divider.
     * @param array $options Options for the divider.
     */
    public function addDivider($isAllowed = true, $key = '',  $sort = false, $cssClass = '') {
        if (!$isAllowed) {
            return $this;
        }
        $divider = array(
            'sort' => $sort,
            'key' => $key,
            'dividerCssClass' => $cssClass
        );

        $this->addKey($divider);
        $divider['dividerCssClass'] = $cssClass.' '.$this->buildCssClass($this->dividerCssClassPrefix, $divider);

        $this->addItem('divider', $divider);
        return $this;
    }

    /**
     * Add a group to the items array.
     *
     * @param array $group The group with the following key(s):
     * - **text**: The text of the group heading.
     * - **icon**: The icon class to appear by header.
     * - **badge**: The header badge such as a count or alert.
     * - **sort**: Specify a custom sort order for the item.
     *    This can be either a number or an array in the form ('before|after', 'key').
     * - **key**: Group key.
     * - **class**: Header CSS class.
     */

    public function addGroup($text = '', $isAllowed = true, $key = '',  $sort = false, $icon = '', $badge = '', $popinRel = '', $cssClass = '') {
        if (!$isAllowed) {
            return $this;
        }
        $group = array(
            'headerText' => $text,
            'headerIcon' => $icon,
            'headerBadge' => $badge,
            'sort' => $sort,
            'key' => $key,
            'headerCssClass' => $cssClass
        );

        $this->addKey($group);

        if ($text) {
            $group['headerCssClass'] = $cssClass.' '.$this->buildCssClass($this->headerCssClassPrefix, $group);
        }
        $this->addItem('group', $group);
        return $this;
    }

    /**
     * Add a link to the menu.
     *
     * @param string|array $key The key of the link. You can nest links in a group by using dot syntax to specify its key.
     * @param array $link The link with the following keys:
     * - **url**: The url of the link.
     * - **text**: The text of the link.
     * - **icon**: The icon class to appear by link.
     * - **badge**: The link contain a badge. such as a count or alert.
     * - **sort**: Specify a custom sort order for the item.
     *    This can be either a number or an array in the form ('before|after', 'key').
     * - **disabled**: Set to true to add disabled style to link.
     * - **key**: Item key.
     * - **class**: CSS class.
     */
    public function addLink($text, $url, $isAllowed = true, $key = false, $sort = false, $icon = '', $badge = '', $popinRel = '', $disabled = false, $cssClass = '') {
        if (!$isAllowed) {
            return $this;
        }

        $link = array(
            'linkText' => $text,
            'linkText' => $text,
            'linkUrl' => $url,
            'linkIcon' => $icon,
            'linkBadge' => $badge,
            'linkPopinRel' => $popinRel,
            'sort' => $sort,
            'key' => $key,
        );

        $this->addKey($link);
        $link['linkCssClass'] = $cssClass.' '.$this->buildCssClass($this->linkCssClassPrefix, $link);

        $listItemCssClasses = array();
        if ($disabled) {
            $listItemCssClasses[] = 'disabled';
        }
        if ($this->isActive($link)) {
            $listItemCssClasses[] = 'active';
        }
        $link['listItemCssClass'] = implode(' ', $listItemCssClasses);

        $this->addItem('link', $link);
        return $this;
    }


    public function hasItems() {
        return (!empty($this->items));
    }


    /**
     * Add a list of links to the menu.
     *
     * @param array $links List of links
     */
    public function addLinks($links) {
        foreach($links as $link) {
            $this->addLinkArray($link);
        }
        return $this;
    }

    public function addKey(&$item) {
        if (!val('key', $item)) {
            $item['key'] = 'item'.$this->keyNumber;
            $this->keyNumber = $this->keyNumber+1;
        }
    }

    /**
     * Add an item to the items array.
     *
     * @param string $type The type of item (link, group, or divider).
     * @param string $key The item key. Dot syntax is allowed to nest items into groups.
     * @param array $item The item to add.
     */
    protected function addItem($type, $item) {
        $this->addKey($item);
        if (!is_array(val('key', $item))) {
            $item['key'] = explode('.', val('key', $item));
        }
        else {
            $item['key'] = array_values(val('key', $item));
        }

        $item = (array)$item;

        // Make sure the link has its type.
        $item['type'] = $type;

        // Set type boolean for mustache logic.
        $item['is'.ucfirst($type)] = true;

        // Explicitly set group as false to aid recursion in mustache.
        if ($type != 'group') {
            $item['isGroup'] = false;
        }

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
     * Adds CSS class[es] to an item, based on 'class' property of an item
     * and also the 'key' property of an item. Prepends prefix to class names.
     *
     * @param string $prefix Prefix to add to class name.
     * @param array $item Item to add CSS class to.
     * @return string
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

    public function isActive($item) {
        $HighlightRoute = Gdn_Url::Request();
        $HighlightUrl = Url($HighlightRoute);

        // Highlight the group.
        return (val('url', $item) && val('url', $item) == $HighlightUrl);
    }

    /**
     * Sort the items in a given dataset (array).
     *
     * @param array $items
     */
    public static function sortItems(&$items) {
        uasort($items, function($a, $b) use ($items) {
            $sort_a = NavModule::sortItemsOrder($a, $items);
            $sort_b = NavModule::sortItemsOrder($b, $items);

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
     * @param int $depth The current recursive depth used to prevent inifinite recursion.
     * @return number
     */
    public static function sortItemsOrder($item, $items, $depth = 0) {
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
                            return NavModule::sortItemsOrder($items[$key], $items, $depth + 1) + 1000;
                        case 'before':
                        default:
                            return NavModule::sortItemsOrder($items[$key], $items, $depth + 1) - 1000;
                    }
                }
            }
        }
        return $default_sort * 10000 + $default_sort;
    }

    public function prepare() {
        if ($this->isRendered) {
            return;
        }
        $this->isRendered = true;
        $this->sortItems($this->items);
        if ($this->flatten) {
            $this->items = $this->flattenArray($this->items);
        }
        else {
            $this->items = $this->numericItemKeys($this->items);
        }

        return !empty($this->items);
    }

    public function numericItemKeys($items) {
        foreach ($items as $key => &$item) {
            unset($item['_sort'], $item['key']);

            // remove empty groups
            if (val('type', $item) == 'group' && !val('items', $item)) {
                unset($items[$key]);
            }

            if (val('items', $item)) {
                $item['items'] = $this->numericItemKeys($item['items']);
                //for mustache logic
                $item['hasChildren'] = true;
            }
            else {
                $item['hasChildren'] = false;
            }
        }
        return array_values($items);
    }

    /**
     * Creates flattened array of menu items.
     *
     * @param array $items
     * @return array
     */
    public function flattenArray($items) {
        $newitems = array();
        foreach($items as $key => $item) {
            unset($item['_sort'], $item['key']);
            $subitems = false;

            // Group item
            if (val('type', $item) == 'group') {
                // ensure groups have items
                if (val('items', $item)) {
                    $subitems = $item['items'];
                    unset($item['items']);
                    if (val('headerText', $item)) {
                        $newitems[] = $item;
                    }
                }
            }
            if ((val('type', $item) != 'group')) {
                $newitems[] = $item;
            }
            if ($subitems) {
                $newitems = array_merge($newitems, $this->flattenArray($subitems));
            }
        }
        return $newitems;
    }
}
