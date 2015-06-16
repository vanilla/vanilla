<?php if (!defined('APPLICATION')) exit();

/**
 * A module for a dropdown.
 *
 * @author Becky Van Bussel <becky@vanillaforums.com>
 * @copyright 2015 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @since 2.3
 */

/**
 * A flawlessly configurable module for a dropdown menu.
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
 *  $dropdown->setView('dropdown-legacy');
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
 * The view is currently a mustache template, which requires the Mustache rendering plugin to be enabled.
 *
 */
class DropdownModule extends SortableModule {

    /**
     * @var string The id of the trigger.
     */
    public $triggerId;


    /**
     * @var array Collection of trigger attributes.
     */
    public $trigger = array('type' => 'button',
                            'isButton' => true,
                            'triggerCssClass' => 'btn-default',
                            'triggerIcon' => 'caret-down');

    /**
     * @var array Allowed trigger types.
     */
    private $triggerTypes = array('button', 'anchor');

    /**
     * @var string The css class of the menulist <ul> block, if any.
     * Bootstrap supports 'dropdown-menu-right' to align the dropdown box to the right of its container
     */
    public $listCssClass = '';

    public $dropdownCssClass;

    /**
     * @var string The top level html wrapper element
     */
    public $tag = 'div';

    /// Methods ///

    public function __construct($triggerId, $triggerText = '', $class = '', $listCssClass = '', $useCssPrefix = false) {
        parent::__construct('dropdown', true, $useCssPrefix);

        $this->trigger['triggerText'] = $triggerText;
        $this->listCssClass = trim($listCssClass);
        $this->triggerId = $triggerId;
        $this->dropdownCssClass = $class;

        if ($useCssPrefix) {
            $this->headerCssClassPrefix = 'dropdown-header';
            $this->linkCssClassPrefix = 'dropdown-link';
            $this->dividerCssClassPrefix = 'divider';
        }
    }

    /**
     * Configure the trigger.
     *
     * @param string $text Text on the button or anchor.
     * @param string $type Trigger type - currently supports 'anchor' or 'button'.
     * @param string $class CSS class on button or anchor tag.
     * @param string $icon Icon span CSS class.
     */
    public function setTrigger($text, $type = 'button', $class = 'btn-default', $icon = 'caret-down') {
        $this->trigger['triggerText'] = $text;
        $this->trigger['type'] = in_array($type, $this->triggerTypes) ? $type : 'button';
        $this->trigger['triggerIcon'] = $icon;
        $this->trigger['triggerCssClass'] = trim($class);

        //for mustache logic
        $this->trigger['isButton'] = $this->trigger['type'] === 'button';
        $this->trigger['isAnchor'] = $this->trigger['type'] === 'anchor';
        return $this;
    }

    public function addBootstrapAssets($controller) {
        $controller->AddCssFile('dropdowns.css', 'dashboard');
        $controller->AddCssFile('buttons.css', 'dashboard');
        $controller->AddCssFile('badges.css', 'dashboard');
        $controller->AddCssFile('type.css', 'dashboard');

        $controller->AddJsFile('dropdown.js', 'dashboard');
    }
}
