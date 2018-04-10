<?php
/**
 * Site nav module.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Collects the links for an application, organizes them by section, and renders the appropriate links given the section.
 *
 * By default, global items display no matter the section we're in.
 *
 * If a section is not specified, the item is added to the SECTION_DEFAULT. If we are in a section without a custom nav,
 * these items will display.
 *
 * We can force the module to display any section menus by setting the currentSections property.
 *
 * TODO: Handle adding dropdown items to the SiteNavModule.
 */
class SiteNavModule extends NavModule {

    /** @var string The section for global items. Items in the global section will render everywhere. */
    const SECTION_GLOBAL = 'globals';

    /** @var string The default section if none is supplied. */
    const SECTION_DEFAULT = 'defaults';

    /** @var string The key for storing links in the sectionItems array. */
    const KEY_LINKS = 'links';

    /** @var string The key for storing groups in the sectionItems array. */
    const KEY_GROUPS = 'groups';

    /**
     * All the nav items organized by section.
     *
     * The first level is the section, the second level is separates items into links and groups,
     * and the third level holds the group or link items.
     *
     * For instance, here's how you'd access the text for a link in the moderation section.
     * $sectionItems['moderation']['links']['moderation.moderation-queue']['text']
     *
     * @var array
     */
    private static $sectionItems = [];

    /** @var bool Whether we've fired the init event yet. */
    private static $initStaticFired = false;

    /** @var array The sections we should render navs for. */
    private $currentSections = [];

    /**
     * @return array The nav items organized by section.
     */
    public static function getSectionItems() {
        return self::$sectionItems;
    }

    /**
     * @param array $sectionItems The nav items organized by section.
     */
    public static function setSectionItems($sectionItems) {
        self::$sectionItems = $sectionItems;
    }

    /**
     * @return boolean Whether we've fired the init event yet.
     */
    public static function isInitStaticFired() {
        return self::$initStaticFired;
    }

    /**
     * @param boolean $initStaticFired Whether we've fired the init event yet.
     */
    public static function setInitStaticFired($initStaticFired) {
        self::$initStaticFired = $initStaticFired;
    }

    /**
     * @return array The sections we should render navs for.
     */
    public function getCurrentSections() {
        return $this->currentSections;
    }

    /**
     * @param array $currentSections The sections we should render navs for.
     * @return SiteNavModule $this
     */
    public function setCurrentSections($currentSections) {
        $this->currentSections = $currentSections;
        return $this;
    }

    /**
     * Add a link to a section.
     *
     * @param string $section The section to add the link to.
     * @param string $text The display text for the link.
     * @param string $url The destination url for the link.
     * @param string $key The item's key (for sorting and CSS targeting).
     * @param string $cssClass The link's CSS class.
     * @param array|int $sort Either a numeric sort position or and array in the style: array('before|after', 'key').
     * @param array $modifiers List of attribute => value, where the attribute is in $this->allowedItemModifiers.
     * @param bool $disabled Whether to disable the link.
     * @return SiteNavModule $this
     */
    public function addLinkToSection($section, $text, $url, $key = '', $cssClass = '', $sort = [], $modifiers = [], $disabled = false) {
        $args = [
            'text' => $text,
            'url' => $url,
            'key' => $key,
            'cssClass' => $cssClass,
            'sort' => $sort,
            'modifiers' => $modifiers,
            'disabled' => $disabled
        ];

        self::addToSectionItems($section, self::KEY_LINKS, $key, $args);
        return $this;
    }

    /**
     * Add a link to a section if it satisfies the $isAllowed condition.
     *
     * @param bool|string|array $isAllowed Either a boolean to indicate whether to actually add the item
     * or a permission string or array of permission strings (full match) to check.
     * @param string $section The section to add the link to.
     * @param string $text The display text for the link.
     * @param string $url The destination url for the link.
     * @param string $key The item's key (for sorting and CSS targeting).
     * @param string $cssClass The link's CSS class.
     * @param array|int $sort Either a numeric sort position or and array in the style: array('before|after', 'key').
     * @param array $modifiers List of attribute => value, where the attribute is in $this->allowedItemModifiers.
     * @param bool $disabled Whether to disable the link.
     * @return SiteNavModule $this
     */
    public function addLinkToSectionIf($isAllowed, $section, $text, $url, $key = '', $cssClass = '', $sort = [], $modifiers = [], $disabled = false) {
        if (!$this->isAllowed($isAllowed)) {
            return $this;
        } else {
            return $this->addLinkToSection($section, $text, $url, $key, $cssClass, $sort, $modifiers, $disabled);
        }
    }

    /**
     * Add a group to a section.
     *
     * @param string $section The section to add the group to.
     * @param string $text The display text for the group header.
     * @param string $key The item's key (for sorting and CSS targeting).
     * @param string $cssClass The group header's CSS class.
     * @param array|int $sort Either a numeric sort position or and array in the style: array('before|after', 'key').
     * @param array $modifiers List of attribute => value, where the attribute is in $this->allowedItemModifiers.
     * @return SiteNavModule $this
     */
    public function addGroupToSection($section, $text = '', $key = '', $cssClass = '', $sort = [], $modifiers = []) {
        $args = [
            'text' => $text,
            'key' => $key,
            'cssClass' => $cssClass,
            'sort' => $sort,
            'modifiers' => $modifiers
        ];

        self::addToSectionItems($section, self::KEY_GROUPS, $key, $args);
        return $this;
    }

    /**
     * Add a group to a section if it satisfies the $isAllowed condition.
     *
     * @param bool|string|array $isAllowed Either a boolean to indicate whether to actually add the item
     * or a permission string or array of permission strings (full match) to check.
     * @param string $section The section to add the group to.
     * @param string $text The display text for the group header.
     * @param string $key The item's key (for sorting and CSS targeting).
     * @param string $cssClass The group header's CSS class.
     * @param array|int $sort Either a numeric sort position or and array in the style: array('before|after', 'key').
     * @param array $modifiers List of attribute => value, where the attribute is in $this->allowedItemModifiers.
     * @return SiteNavModule $this
     */
    public function addGroupToSectionIf($isAllowed, $section, $text = '', $key = '', $cssClass = '', $sort = [], $modifiers = []) {
        if (!$this->isAllowed($isAllowed)) {
            return $this;
        } else {
            return $this->addGroupToSection($section, $text, $key, $cssClass, $sort, $modifiers);
        }
    }

    /**
     * Add an item to a section in the section items array.
     *
     * @param string $section The section to add the group to.
     * @param string $typeKey Either KEY_LINKS or KEY_GROUPS
     * @param string $itemKey The key for the item.
     * @param string $item The item to add to the section items array.
     */
    private static function addToSectionItems($section, $typeKey, $itemKey, $item) {
        self::$sectionItems[strtolower($section)][$typeKey][$itemKey] = $item;
    }

    /**
     * Add a link to the default section.
     *
     * @param string $text The display text for the link.
     * @param string $url The destination url for the link.
     * @param string $key The item's key (for sorting and CSS targeting).
     * @param string $cssClass The link's CSS class.
     * @param array|int $sort Either a numeric sort position or and array in the style: array('before|after', 'key').
     * @param array $modifiers List of attribute => value, where the attribute is in $this->allowedItemModifiers.
     * @param bool $disabled Whether to disable the link.
     * @return SiteNavModule $this
     */
    public function addLink($text, $url, $key = '', $cssClass = '', $sort = [], $modifiers = [], $disabled = false) {
        $this->addLinkToSection(static::SECTION_DEFAULT, $text, $url, $key, $cssClass, $sort, $modifiers, $disabled);
        return $this;
    }

    /**
     * Add a link to the default section if it satisfies the $isAllowed condition.
     *
     * @param bool|string|array $isAllowed Either a boolean to indicate whether to actually add the item
     * or a permission string or array of permission strings (full match) to check.
     * @param string $text The display text for the link.
     * @param string $url The destination url for the link.
     * @param string $key The item's key (for sorting and CSS targeting).
     * @param string $cssClass The link's CSS class.
     * @param array|int $sort Either a numeric sort position or and array in the style: array('before|after', 'key').
     * @param array $modifiers List of attribute => value, where the attribute is in $this->allowedItemModifiers.
     * @param bool $disabled Whether to disable the link.
     * @return SiteNavModule $this
     */
    public function addLinkIf($isAllowed, $text, $url, $key = '', $cssClass = '', $sort = [], $modifiers = [], $disabled = false) {
        if (!$this->isAllowed($isAllowed)) {
            return $this;
        } else {
            return $this->addLink($text, $url, $key, $cssClass, $sort, $modifiers, $disabled);
        }
    }

    /**
     * Add a group to the default section.
     *
     * @param string $text The display text for the group header.
     * @param string $key The item's key (for sorting and CSS targeting).
     * @param string $cssClass The group header's CSS class.
     * @param array|int $sort Either a numeric sort position or and array in the style: array('before|after', 'key').
     * @param array $modifiers List of attribute => value, where the attribute is in $this->allowedItemModifiers.
     * @return SiteNavModule $this
     */
    public function addGroup($text = '', $key = '', $cssClass = '', $sort = [], $modifiers = []) {
        $this->addGroupToSection(static::SECTION_DEFAULT, $text, $key, $cssClass, $sort, $modifiers);
        return $this;
    }

    /**
     * Add a group to the default section if it satisfies the $isAllowed condition.
     *
     * @param bool|string|array $isAllowed Either a boolean to indicate whether to actually add the item
     * or a permission string or array of permission strings (full match) to check.
     * @param string $text The display text for the group header.
     * @param string $key The item's key (for sorting and CSS targeting).
     * @param string $cssClass The group header's CSS class.
     * @param array|int $sort Either a numeric sort position or and array in the style: array('before|after', 'key').
     * @param array $modifiers List of attribute => value, where the attribute is in $this->allowedItemModifiers.
     * @return SiteNavModule $this
     */
    public function addGroupIf($isAllowed = true, $text = '', $key = '', $cssClass = '', $sort = [], $modifiers = []) {
        if (!$this->isAllowed($isAllowed)) {
            return $this;
        } else {
            return $this->addGroup($text, $key, $cssClass, $sort, $modifiers);
        }
    }

    /**
     * Add a link to the globals section.
     * Unless currentSections is explicitly set, items in the global section will be rendered in every section.
     *
     * @param string $text The display text for the link.
     * @param string $url The destination url for the link.
     * @param string $key The item's key (for sorting and CSS targeting).
     * @param string $cssClass The link's CSS class.
     * @param array|int $sort Either a numeric sort position or and array in the style: array('before|after', 'key').
     * @param array $modifiers List of attribute => value, where the attribute is in $this->allowedItemModifiers.
     * @param bool $disabled Whether to disable the link.
     * @return SiteNavModule $this
     */
    public function addLinkToGlobals($text, $url, $key = '', $cssClass = '', $sort = [], $modifiers = [], $disabled = false) {
        $this->addLinkToSection(static::SECTION_GLOBAL, $text, $url, $key, $cssClass, $sort, $modifiers, $disabled);
        return $this;
    }

    /**
     * Add a link to the globals section if it satisfies the $isAllowed condition.
     * Unless currentSections is explicitly set, items in the global section will be rendered in every section.
     *
     * @param bool|string|array $isAllowed Either a boolean to indicate whether to actually add the item
     * or a permission string or array of permission strings (full match) to check.
     * @param string $text The display text for the link.
     * @param string $url The destination url for the link.
     * @param string $key The item's key (for sorting and CSS targeting).
     * @param string $cssClass The link's CSS class.
     * @param array|int $sort Either a numeric sort position or and array in the style: array('before|after', 'key').
     * @param array $modifiers List of attribute => value, where the attribute is in $this->allowedItemModifiers.
     * @param bool $disabled Whether to disable the link.
     * @return SiteNavModule $this
     */
    public function addLinkToGlobalsIf($isAllowed = true, $text, $url, $key = '', $cssClass = '', $sort = [], $modifiers = [], $disabled = false) {
        if (!$this->isAllowed($isAllowed)) {
            return $this;
        } else {
            return $this->addLinkToGlobals($text, $url, $key, $cssClass, $sort, $modifiers, $disabled);
        }
    }

    /**
     * Add a group to the global section.
     * Unless currentSections is explicitly set, items in the global section will be rendered in every section.
     *
     * @param string $text The display text for the group header.
     * @param string $key The item's key (for sorting and CSS targeting).
     * @param string $cssClass The group header's CSS class.
     * @param array|int $sort Either a numeric sort position or and array in the style: array('before|after', 'key').
     * @param array $modifiers List of attribute => value, where the attribute is in $this->allowedItemModifiers.
     * @return SiteNavModule $this
     */
    public function addGroupToGlobals($text = '', $key = '', $cssClass = '', $sort = [], $modifiers = []) {
        $this->addGroupToSection(static::SECTION_GLOBAL, $text, $key, $cssClass, $sort, $modifiers);
        return $this;
    }

    /**
     * Add a group to the globals section if it satisfies the $isAllowed condition.
     * Unless currentSections is explicitly set, items in the global section will be rendered in every section.
     *
     * @param bool|string|array $isAllowed Either a boolean to indicate whether to actually add the item
     * or a permission string or array of permission strings (full match) to check.
     * @param string $text The display text for the group header.
     * @param string $key The item's key (for sorting and CSS targeting).
     * @param string $cssClass The group header's CSS class.
     * @param array|int $sort Either a numeric sort position or and array in the style: array('before|after', 'key').
     * @param array $modifiers List of attribute => value, where the attribute is in $this->allowedItemModifiers.
     * @return SiteNavModule $this
     */
    public function addGroupToGlobalsIf($isAllowed, $text = '', $key = '', $cssClass = '', $sort = [], $modifiers = []) {
        if (!$this->isAllowed($isAllowed)) {
            return $this;
        } else {
            return $this->addGroupToGlobals($text, $key, $cssClass, $sort, $modifiers);
        }
    }


    /**
     * If current sections is not set, try to find the section we're in and then add the groups and links
     * for that section to the nav.
     *
     * @return bool Whether we're given clearance to render the nav.
     * @throws Exception
     */
    public function prepare() {

        if (!self::isInitStaticFired()) {
            self::setInitStaticFired(true);
            $this->fireEvent('init');
        }

        if (empty($this->currentSections)) {
            $currentSections = Gdn_Theme::section('', 'get');
            $currentSections = array_map('strtolower', $currentSections);

            $customNavKeys = array_intersect(array_keys(self::getSectionItems()), $currentSections);
            $hasCustomNav = !empty($customNavKeys);

            // No custom nav, display the default section nav.
            if (!$hasCustomNav) {
                $currentSections = [static::SECTION_DEFAULT];
            }

            // Add global items
            $currentSections[] = static::SECTION_GLOBAL;
        } else {
            $currentSections = array_map('strtolower', $this->currentSections);
        }

        foreach ($currentSections as $currentSection) {
            if ($section = val(strtolower($currentSection), self::getSectionItems())) {
                $this->addSectionItems($section);
            }
        }
        return parent::prepare();
    }

    /**
     * Cycle through the items for a section and add them to the nav to be rendered.
     *
     * @param array $sectionItems The section items to render.
     */
    public function addSectionItems($sectionItems) {
        if ($groups = val(self::KEY_GROUPS, $sectionItems)) {
            foreach ($groups as $group) {
                parent::addGroup(
                    $group['text'],
                    $group['key'],
                    $group['cssClass'],
                    $group['sort'],
                    $group['modifiers']
                );
            }
        }

        if ($links = val(self::KEY_LINKS, $sectionItems)) {
            foreach ($links as $link) {
                parent::addLink(
                    $link['text'],
                    $link['url'],
                    $link['key'],
                    $link['cssClass'],
                    $link['sort'],
                    $link['modifiers'],
                    $link['disabled']
                );
            }
        }
    }

    /**
     * Remove an item from the nested set.
     *
     * @param string $key The key of the item to remove, separated by dots.
     * @param string $typeKey
     */
    public function removeItem($key, $typeKey = self::KEY_LINKS) {
        foreach (self::$sectionItems as &$section) {
            unset($section[$typeKey][$key]);
        }
    }
}
