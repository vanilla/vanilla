<?php

/**
 * A dropdown menu module.
 *
 * @author Becky Van Bussel <becky@vanillaforums.com>
 * @copyright 2015 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @since 2.3
 */

/**
 * A flawlessly configurable dropdown menu module.
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
 * For example, you could set the sort property to an item to array('before'=>'key1')
 * and it would place the item before another item with the key of 'key1'.
 * If you have a group with the key of 'key2', you can add to this group by
 * setting the key of a new item to 'key2.newItemKey'.
 * The sort property can also be an integer, indicating the item's position in the menu.
 *
 *
 * Here is an example menu creation:
 *
 * $dropdown = new dropdownModule('my-dropdown');
 * $dropdown->setTrigger('A New Name')
 * ->addLink('Link 1', '#') // automatically creates key: item1
 * ->addDivider() // automatically creates key: item2
 * ->addLink('Link 2', '#', 'link2', 'danger') // creates item with key: link2
 * ->addLink('Link 3', '#') // automatically creates key: item3
 * ->addLink('Link 4', '#') // automatically creates key: item4
 * ->addGroup('', 'group1') // creates group with no header
 * ->addGroup('Group 3', 'group3') // creates group with header: 'Group 3', empty so will not display
 * ->addGroup('Group 2', 'group2') // creates group with header: 'Group 2'
 * ->addLink('Link 5', '#', '', '', array('before', 'link2'), array('badge' => '4')) // automatically creates key: item5. Inserts before Link 2
 * ->addLink('Link 6', '#') // automatically creates key: item6
 * ->addLink('Link 7', '#') // automatically creates key: item7
 * ->addLink('Link 8', '#', 'group2.link8', '', array(), array('icon' => 'flame')) // adds to Group 2
 * ->addLink('Link 9', '#', 'group1.link9') // adds to Group 1
 * ->addLink('Link 10', '#', 'group1.link10'); // adds to Group 1
 * echo $dropdown;
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
class DropdownModule extends Gdn_Module {

    use NestedCollection;

    /**
     * @var string The id value of the trigger.
     */
    private $triggerId;

    /**
     * @var array Collection of attributes for the dropdown menu trigger.
     * - **type**: string - One of the $triggerTypes.
     * - **text**: string - Text on the trigger.
     * - **cssClass**: string - CSS class for the trigger.
     * - **icon**: string - Icon for the trigger.
     */
    private $trigger = ['type' => 'button',
                            'text' => '',
                            'cssClass' => '',
                            'icon' => 'caret-down'];

    /**
     * @var array Allowed trigger types.
     */
    private $triggerTypes = ['button', 'anchor'];

    /**
     * @var string A potential CSS class of the dropdown menu wrapper container.
     */
    private $cssClass;

    /**
     * @var string A potential CSS class of the list <ul> block.
     */
    private $listCssClass = '';

    /**
     * @var string The dropdown menu wrapper container element tag.
     */
    private $tag = 'div';

    /**
     * Constructor.
     *
     * @param string $triggerId The html id value of the trigger tag. Needs to be unique.
     * @param string $triggerText Text on the trigger.
     * @param string $cssClass A potential CSS class of the dropdown menu wrapper container.
     * @param string $listCssClass A potential CSS class of the list <ul> block.
     * @param bool $useCssPrefix Whether to use CSS prefixes on the dropmenu items.
     */
    public function __construct($triggerId = 'dropdown', $triggerText = '', $cssClass = '', $listCssClass = '', $useCssPrefix = true) {
        parent::__construct();
        $this->flatten = true;
        $this->useCssPrefix = $useCssPrefix;

        $this->triggerId = $triggerId;
        $this->trigger['text'] = $triggerText;
        $this->cssClass = $cssClass;
        $this->listCssClass = trim($listCssClass);

        if ($useCssPrefix) {
            $this->headerCssClassPrefix = 'dropdown-header';
            $this->linkCssClassPrefix = 'dropdown-menu-link';
            $this->dividerCssClassPrefix = 'divider';
        }
    }

    /**
     * @return array
     */
    public function getTrigger() {
       return $this->trigger;
    }

    /**
     * @return string
     */
    public function getTriggerId() {
        return $this->triggerId;
    }

    /**
     * @param string $triggerId
     */
    public function setTriggerId($triggerId) {
        $this->triggerId = $triggerId;
    }

    /**
     * @return array
     */
    public function getTriggerTypes() {
        return $this->triggerTypes;
    }

    /**
     * @param array $triggerTypes
     */
    public function setTriggerTypes($triggerTypes) {
        $this->triggerTypes = $triggerTypes;
    }

    /**
     * @return string
     */
    public function getCssClass() {
        return $this->cssClass;
    }

    /**
     * @param string $cssClass
     */
    public function setCssClass($cssClass) {
        $this->cssClass = $cssClass;
    }

    /**
     * @return string
     */
    public function getListCssClass() {
        return $this->listCssClass;
    }

    /**
     * @param string $listCssClass
     */
    public function setListCssClass($listCssClass) {
        $this->listCssClass = $listCssClass;
    }

    /**
     * @return string
     */
    public function getTag() {
        return $this->tag;
    }

    /**
     * @param string $tag
     */
    public function setTag($tag) {
        $this->tag = $tag;
    }

    /**
     * Configure the trigger.
     *
     * @param string $text Text on the trigger.
     * @param string $type One of the triggerTypes - currently supports 'anchor' or 'button'.
     * @param string $cssClass CSS class for the trigger.
     * @param string $icon Icon for the trigger.
     * @param string $url If the trigger has a fallback href for non-js users, add the url here.
     * @param array $attributes The attributes to add to the trigger.
     * @return object $this The calling DropdownModule object.
     */
    public function setTrigger($text = '', $type = 'button', $cssClass = 'btn-default', $icon = 'caret-down', $url = '', $attributes = []) {
        $this->trigger['text'] = $text;
        $this->trigger['type'] = in_array($type, $this->triggerTypes) ? $type : 'button';
        $this->trigger['icon'] = $icon;
        $this->trigger['cssClass'] = trim($cssClass);
        $this->trigger['attributes'] = $attributes;
        $this->trigger['url'] = $url;
        return $this;
    }
}
