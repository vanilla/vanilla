<?php
/**
 * A module for a list of links.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.2
 */

/**
 * A module for a list of links. This is a generic module that can be used or subclassed.
 *
 * The module contains an array of items. Each item can be one of the following types: link, group, or divider.
 * When adding an item you provide an array of with the following keys.
 */
class NavModule extends Gdn_Module {

    /** @var string The css class of the menu, if any. */
    public $cssClass = null;

    /** @var string The id of the menu, if any. */
    public $id = null;

    /** @var array An array of items in the menu. */
    protected $items = array();

    /**
     *
     *
     * @param string $Sender
     */
    public function __construct($Sender = '') {
        $this->_ApplicationFolder = 'dashboard';

        parent::__construct($Sender);
    }

    /**
     * Add a divider to the items array.
     *
     * @param string $key The key of the divider.
     * @param array $options Options for the divider.
     */
    public function addDivider($key, $options = array()) {
        $this->addItem('divider', $key, $options);
    }

    /**
     * Add a group to the items array.
     *
     * @param string $key The group key. Dot syntax is allowed to nest groups within eachother.
     * @param array $group The group with the following key(s):
     * - **text**: The text of the group. Html is allowed.
     * - **sort**: Specify a custom sort order for the item.
     *   This can be either a number or an array in the form ('before|after', 'key').
     */
    public function addGroup($key, $group) {
        $this->addItem('group', $key, $group);
    }

    /**
     * Add an item to the items array.
     *
     * @param string $type The type of item (link, group, or divider).
     * @param string $key The item key. Dot syntax is allowed to nest items into groups.
     * @param array $item The item to add.
     */
    protected function addItem($type, $key, $item) {
        if (!is_array($key)) {
            $key = explode('.', $key);
        } else {
            $key = array_values($key);
        }

        $item = (array)$item;

        // Make sure the link has its type.
        $item['type'] = $type;

        // Walk into the items list to set the item.
        $items =& $this->items;
        foreach ($key as $i => $key_part) {
            if ($i === count($key) - 1) {
                // Add the item here.
                if (array_key_exists($key_part, $items)) {
                    // The item is already here so merge this one on top of it.
                    if ($items[$key_part]['type'] !== $type) {
                        throw new \Exception("$key of type $type does not match exsisting type {$items[$key_part]['type']}.", 500);
                    }

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
     * Add a link to the menu.
     *
     * @param string|array $key The key of the link. You can nest links in a group by using dot syntax to specify its key.
     * @param array $link The link with the following keys:
     * - **url**: The url of the link.
     * - **text**: The text of the link. Html is allowed.
     * - **icon**: The html of the icon.
     * - **badge**: The link contain a badge. such as a count or alert. Html is allowed.
     * - **sort**: Specify a custom sort order for the item.
     *   This can be either a number or an array in the form ('before|after', 'key').
     */
    public function addLink($key, $link) {
        $this->addItem('link', $key, $link);
    }

    protected function getAttibutes() {
        $attributes = array('id' => $this->id, 'class' => $this->cssClass, 'role' => 'navigation');

        return attribute($attributes);
    }

    protected function getCssClass($key, $item) {
        $result = val('class', $item, '')." nav-$key";
        return trim($result);
    }

    protected function itemVisible($key, $item) {
        $visible = val('visible', $item, true);
        $prop = 'show'.$key;

        if (property_exists($this, $prop)) {
            return $this->$prop;
        } else {
            return $visible;
        }
    }

    /**
     * Render the menu as a nav.
     */
    public function render() {
        echo '<nav '.$this->getAttibutes().">\n";
        $this->renderItems($this->items);
        echo "</nav>\n";
    }

    /**
     *
     *
     * @param $items
     * @param int $level
     */
    protected function renderItems($items, $level = 0) {
        NavModule::sortItems($items);

        foreach ($items as $key => $item) {
            $visible = $this->itemVisible($key, $item);
            if (!$visible) {
                continue;
            }

            switch ($item['type']) {
                case 'link':
                    $this->renderLink($key, $item);
                    break;
                case 'group':
                    $this->renderGroup($key, $item, $level);
                    break;
                case 'divider':
                    $this->renderDivider($key, $item);
                    break;
                default:
                    echo "\n<!-- Item $key has an unknown type {$item['type']}. -->\n";
            }
        }
    }

    /**
     *
     *
     * @param $key
     * @param $link
     */
    protected function renderLink($key, $link) {
        $href = $link['url'];
        $text = $link['text'];
        $icon = val('icon', $link);
        $badge = val('badge', $link);
        $class = 'nav-link '.$this->getCssClass($key, $link);
        unset($link['url'], $link['text'], $link['class'], $link['icon'], $link['badge']);

        if ($icon) {
            $text = $icon.' <span class="text">'.$text.'</span>';
        }

        if ($badge) {
            if (is_numeric($badge)) {
                $badge = wrap(number_format($badge), 'span', array('class' => 'Count'));
            }
            $text = '<span class="Aside">'.$badge.'</span> '.$text;
        }

        echo anchor($text, $href, $class, $link, true)."\n";
    }

    /**
     *
     *
     * @param $key
     * @param $group
     * @param int $level
     */
    protected function renderGroup($key, $group, $level = 0) {
        $text = $group['text'];
        $group['class'] = 'nav-group '.($text ? '' : 'nav-group-noheading ').$this->getCssClass($key, $group);

        $items = $group['items'];
        unset($group['text'], $group['items']);

        // Don't render an empty group.
        if (empty($items)) {
            return;
        }

        echo '<div '.attribute($group).">\n";

        // Write the heading.
        if ($text) {
            echo "<h3>$text</h3>\n";
        }

        // Write the group items.
        $this->renderItems($items, $level + 1);

        echo "</div>\n";
    }

    /**
     *
     *
     * @param $key
     * @param $divider
     */
    protected function renderDivider($key, $divider) {
        echo "<div class=\"nav-divider\"></div>\n";
    }

    /**
     * Sort the items in a given dataset (array).
     *
     * @param array $items
     */
    public static function sortItems(&$items) {
        uasort($items, function ($a, $b) use ($items) {
            $sort_a = NavModule::sortItemsOrder($a, $items);
            $sort_b = NavModule::sortItemsOrder($b, $items);

            if ($sort_a > $sort_b) {
                return 1;
            } elseif ($sort_a < $sort_b)
                return -1;
            else {
                return 0;
            }
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

    /**
     *
     *
     * @return string
     */
    public function toString() {
        ob_start();
        $this->render();
        $result = ob_get_clean();

        return $result;
    }
}

if (!function_exists('icon')) :
    /**
     * Return icon HTML.
     *
     * @param $name
     * @return string
     */
    function icon($name) {
        return <<<EOT
<span class="icon icon-$name"></span>
EOT;
    }

endif;
