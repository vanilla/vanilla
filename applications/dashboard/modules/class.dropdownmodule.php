<?php if (!defined('APPLICATION')) exit();

/**
 * A dropdown menu component.
 *
 * @author Becky Van Bussel <becky@vanillaforums.com>
 * @copyright 2015 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @since 2.3
 */

/**
 * A flawlessly configurable dropdown menu component.
 *
 * The module includes a dropdown trigger and menu items. Menu items can be
 *
 * **link**    - An link item.
 * **group**   - A group item to create a logical grouping of menu items
 *               for sorting purposes, and/or to create a heading.
 * **divider** - A dividing line.
 *
 * Each item must have a unique key. If not supplied, the class will generate
 * one in the format: 'item*', where * is an auto incrementing number.
 * Keys can be used for sorting purposes and for adding links to a group.
 * For example, you could add a property to an item of 'sort'=>array('before'=>'key1')
 * and it would place the item before another item with the key of 'key1'.
 * If you have a group with the key of 'key2', you can add to this group by
 * adding a new item with the property of 'key'=>'key2.newItemKey'
 *
 *
 * Here is an example menu creation:
 *
 *  $dropdown = new DropdownModule('my-dropdown', 'Trigger Name');
 *  $dropdown->addBootstrapAssets($this);
 *  $dropdown->setTrigger('A New Name', 'button', 'btn-default', 'caret-down')
 *  ->addLink('Link 1', '#') // automatically creates key: item1
 *  ->addDivider() // automatically creates key: item2
 *  ->addLink('Link 2', '#', true, 'link2', false, '', '', false, 'bg-danger') // creates item with key: link2
 *  ->addLink('Link 3', '#') // automatically creates key: item3
 *  ->addLink('Link 4', '#') // automatically creates key: item4
 *  ->addGroup('', true, 'group1') // creates group with no header
 *  ->addGroup('Group 3', true, 'group3') // creates group with header: 'Group 3', empty so will not display
 *  ->addGroup('Group 2', true, 'group2') // creates group with header: 'Group 2'
 *  ->addLink('Link 5', '#', true, false, array('before', 'link2'), '', '4') // automatically creates key: item5. Inserts before Link 2
 *  ->addLink('Link 6', '#') // automatically creates key: item6
 *  ->addLink('Link 7', '#') // automatically creates key: item7
 *  ->addLink('Link 8', '#', true, 'group2.link8', false, 'flame', '', true) // adds to Group 2
 *  ->addLink('Link 9', '#', true, 'group1.link9') // adds to Group 1
 *  ->addLink('Link 10', '#', true, 'group1.link10'); // adds to Group 1
 *
 * Which results in a menu:
 *
 *  Trigger Name
 *
 *  Link 1
 *  ------------
 *  Link 5
 *  Link 2
 *  Link 3
 *  Link 4
 *  Link 9
 *  Link 10
 *  Group 2
 *  Link 8
 *  Link 6
 *  Link 7
 *
 *
 */
class DropdownModule extends SortableModule {

    /**
     * @var string The id value of the trigger.
     */
    public $triggerId;

    /**
     * @var array Collection of attributes for the dropdown menu trigger.
     * - **type**: string - One of the $triggerTypes.
     * - **isButton**: bool - Helper flag for Mustache template rendering.
     * - **isAnchor**: bool - Helper flag for Mustache template rendering.
     * - **triggerText**: string - Text on the trigger.
     * - **triggerCssClass**: string - CSS class for the trigger.
     * - **triggerIcon**: string - Icon for the trigger.
     */
    public $trigger = array('type' => 'button',
                            'isButton' => true,
                            'isAnchor' => false,
                            'triggerText' => '',
                            'triggerCssClass' => 'btn-default',
                            'triggerIcon' => 'caret-down');

    /**
     * @var array Allowed trigger types.
     */
    private $triggerTypes = array('button', 'anchor');

    /**
     * @var string A potential CSS class of the dropdown menu wrapper container.
     */
    public $dropdownCssClass;

    /**
     * @var string A potential CSS class of the list <ul> block.
     */
    public $listCssClass = ''; // Twitter Bootstrap supports 'dropdown-menu-right' to align the dropdown box to the right of its container.

    /**
     * @var string The dropdown menu wrapper container element tag.
     */
    public $tag = 'div';

    /**
     * Constructor.
     *
     * @param string $triggerId The id value of the trigger.
     * @param string $triggerText Text on the trigger.
     * @param string $dropdownCssClass A potential CSS class of the dropdown menu wrapper container.
     * @param string $listCssClass A potential CSS class of the list <ul> block.
     * @param bool $useCssPrefix Whether to use CSS prefixes on the dropmenu items.
     */
    public function __construct($triggerId, $triggerText = '', $dropdownCssClass = '', $listCssClass = '', $useCssPrefix = true) {
        parent::__construct('dropdown', true, $useCssPrefix);
        $this->triggerId = $triggerId;
        $this->trigger['triggerText'] = $triggerText;
        $this->dropdownCssClass = $dropdownCssClass;
        $this->listCssClass = trim($listCssClass);

        if ($useCssPrefix) {
            $this->headerCssClassPrefix = 'dropdown-header';
            $this->linkCssClassPrefix = 'dropdown-menu-link';
            $this->dividerCssClassPrefix = 'divider';
        }
    }

    /**
     * Configure the trigger.
     *
     * @param string $text Text on the trigger.
     * @param string $type One of the triggerTypes - currently supports 'anchor' or 'button'.
     * @param string $class CSS class for the trigger.
     * @param string $icon Icon for the trigger.
     * @return object $this The calling DropdownModule object.
     */
    public function setTrigger($text = '', $type = 'button', $class = 'btn-default', $icon = 'caret-down') {
        $this->trigger['triggerText'] = $text;
        $this->trigger['type'] = in_array($type, $this->triggerTypes) ? $type : 'button';
        $this->trigger['triggerIcon'] = $icon;
        $this->trigger['triggerCssClass'] = trim($class);

        //for mustache logic
        $this->trigger['isButton'] = $this->trigger['type'] === 'button';
        $this->trigger['isAnchor'] = $this->trigger['type'] === 'anchor';
        return $this;
    }

    /**
     * Adds necessary Twitter Bootstrap assets for the dropdown menu to render.
     * The default view requires these assets in order to render properly.
     *
     * @param $controller Controller object rendering the dropdown menu.
     * @return object $this The calling DropdownModule object.
     */
    public function addBootstrapAssets($controller) {
        $controller->AddCssFile('dropdowns.css', 'dashboard');
        $controller->AddCssFile('buttons.css', 'dashboard');
        $controller->AddCssFile('badges.css', 'dashboard');
        $controller->AddCssFile('type.css', 'dashboard');

        $controller->AddJsFile('dropdown.js', 'dashboard');
        return $this;
    }
}
