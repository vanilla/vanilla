<?php
/**
 * Site nav module.
 *
 * @copyright 2009-2016 Vanilla Forums Inc.
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
 * We can force the module to display any section menus by setting the currentSections property. Beware, you'll need to
 * handle the user preference saving if you do this.
 *
 * TODO: Handle the dropdown menu case.
 */
class SiteNavModule extends NavModule {

    const SECTION_GLOBAL = 'globals';
    const SECTION_DEFAULT = 'defaults';
    const LINKS_INDEX = 'links';
    const GROUPS_INDEX = 'groups';

    /** @var array  */
    protected static $sectionItems = [];

    /** @var bool */
    protected static $initStaticFired = false;

    /** @var array */
    protected $currentSections = [];


    /**
     * @return array
     */
    public function getCurrentSections() {
        return $this->currentSections;
    }

    /**
     * @param array $currentSections
     * @return $this
     */
    public function setCurrentSections($currentSections) {
        $this->currentSections = $currentSections;
        return $this;
    }

    /**
     * @param $section
     * @param $text
     * @param $url
     * @param string $key
     * @param string $cssClass
     * @param array $sort
     * @param array $modifiers
     * @param bool $disabled
     * @return $this
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
        self::$sectionItems[strtolower($section)][self::LINKS_INDEX][$key] = $args;
        return $this;
    }

    /**
     * @param $isAllowed
     * @param $section
     * @param $text
     * @param $url
     * @param string $key
     * @param string $cssClass
     * @param array $sort
     * @param array $modifiers
     * @param bool $disabled
     * @return $this|SiteNavModule
     */
    public function addLinkToSectionIf($isAllowed, $section, $text, $url, $key = '', $cssClass = '', $sort = [], $modifiers = [], $disabled = false) {
        if (!$this->isAllowed($isAllowed)) {
            return $this;
        } else {
            return $this->addLinkToSection($section, $text, $url, $key, $cssClass, $sort, $modifiers, $disabled);
        }
    }

    /**
     * @param $section
     * @param string $text
     * @param string $key
     * @param string $cssClass
     * @param array $sort
     * @param array $modifiers
     * @return $this
     */
    public function addGroupToSection($section, $text = '', $key = '', $cssClass = '', $sort = [], $modifiers = []) {
        $args = [
            'text' => $text,
            'key' => $key,
            'cssClass' => $cssClass,
            'sort' => $sort,
            'modifiers' => $modifiers
        ];
        self::$sectionItems[strtolower($section)][self::GROUPS_INDEX][] = $args;
        return $this;
    }

    /**
     * @param $isAllowed
     * @param $section
     * @param string $text
     * @param string $key
     * @param string $cssClass
     * @param array $sort
     * @param array $modifiers
     * @return $this|SiteNavModule
     */
    public function addGroupToSectionIf($isAllowed, $section, $text = '', $key = '', $cssClass = '', $sort = [], $modifiers = []) {
        if (!$this->isAllowed($isAllowed)) {
            return $this;
        } else {
            return $this->addGroupToSection($section, $text, $key, $cssClass, $sort, $modifiers);
        }
    }

    /**
     * @param string $text
     * @param string $url
     * @param string $key
     * @param string $cssClass
     * @param array $sort
     * @param array $modifiers
     * @param bool $disabled
     * @return $this
     */
    public function addLink($text, $url, $key = '', $cssClass = '', $sort = [], $modifiers = [], $disabled = false) {
        $this->addLinkToSection(self::SECTION_DEFAULT, $text, $url, $key, $cssClass, $sort, $modifiers, $disabled);
        return $this;
    }

    /**
     * @param array|bool|string $isAllowed
     * @param string $text
     * @param string $url
     * @param string $key
     * @param string $cssClass
     * @param array $sort
     * @param array $modifiers
     * @param bool $disabled
     * @return $this|SiteNavModule
     */
    public function addLinkIf($isAllowed, $text, $url, $key = '', $cssClass = '', $sort = [], $modifiers = [], $disabled = false) {
        if (!$this->isAllowed($isAllowed)) {
            return $this;
        } else {
            return $this->addLink($text, $url, $key, $cssClass, $sort, $modifiers, $disabled);
        }
    }

    /**
     * @param string $text
     * @param string $key
     * @param string $cssClass
     * @param array $sort
     * @param array $modifiers
     * @return $this
     */
    public function addGroup($text = '', $key = '', $cssClass = '', $sort = [], $modifiers = []) {
        $this->addGroupToSection(self::SECTION_DEFAULT, $text, $key, $cssClass, $sort, $modifiers);
        return $this;
    }

    /**
     * @param array|bool|string $isAllowed
     * @param string $text
     * @param string $key
     * @param string $cssClass
     * @param array $sort
     * @param array $modifiers
     * @return $this|SiteNavModule
     */
    public function addGroupIf($isAllowed = true, $text = '', $key = '', $cssClass = '', $sort = [], $modifiers = []) {
        if (!$this->isAllowed($isAllowed)) {
            return $this;
        } else {
            return $this->addGroup($text, $key, $cssClass, $sort, $modifiers);
        }
    }

    /**
     * @param $text
     * @param $url
     * @param string $key
     * @param string $cssClass
     * @param array $sort
     * @param array $modifiers
     * @param bool $disabled
     * @return $this
     */
    public function addLinkToGlobals($text, $url, $key = '', $cssClass = '', $sort = [], $modifiers = [], $disabled = false) {
        $this->addLinkToSection(self::SECTION_GLOBAL, $text, $url, $key, $cssClass, $sort, $modifiers, $disabled);
        return $this;
    }

    /**
     * @param bool $isAllowed
     * @param $text
     * @param $url
     * @param string $key
     * @param string $cssClass
     * @param array $sort
     * @param array $modifiers
     * @param bool $disabled
     * @return $this|SiteNavModule
     */
    public function addLinkToGlobalsIf($isAllowed = true, $text, $url, $key = '', $cssClass = '', $sort = [], $modifiers = [], $disabled = false) {
        if (!$this->isAllowed($isAllowed)) {
            return $this;
        } else {
            return $this->addLinkToGlobals($text, $url, $key, $cssClass, $sort, $modifiers, $disabled);
        }
    }

    /**
     * @param string $text
     * @param string $key
     * @param string $cssClass
     * @param array $sort
     * @param array $modifiers
     * @return $this
     */
    public function addGroupToGlobals($text = '', $key = '', $cssClass = '', $sort = [], $modifiers = []) {
        $this->addGroupToSection(self::SECTION_GLOBAL, $text, $key, $cssClass, $sort, $modifiers);
        return $this;
    }

    /**
     * @param $isAllowed
     * @param string $text
     * @param string $key
     * @param string $cssClass
     * @param array $sort
     * @param array $modifiers
     * @return $this|SiteNavModule
     */
    public function addGroupToGlobalsIf($isAllowed, $text = '', $key = '', $cssClass = '', $sort = [], $modifiers = []) {
        if (!$this->isAllowed($isAllowed)) {
            return $this;
        } else {
            return $this->addGroupToGlobals($text, $key, $cssClass, $sort, $modifiers);
        }
    }


    /**
     * @return bool
     * @throws Exception
     */
    public function prepare() {

        if (!self::$initStaticFired) {
            self::$initStaticFired = true;
            $this->fireEvent('init');
        }

        if (empty($this->currentSections)) {
            $currentSections = Gdn_Theme::section('', 'get');
            $currentSections = array_map('strtolower', $currentSections);

            $customMenuKeys = array_intersect(array_keys(self::$sectionItems), $currentSections);
            $hasCustomMenu = !empty($customMenuKeys);

            if (!$hasCustomMenu) {
                $currentSections = [self::SECTION_DEFAULT];
            }

            // Add global items
            $currentSections[] = self::SECTION_GLOBAL;
        } else {
            $currentSections = array_map('strtolower', $this->currentSections);
        }

        foreach ($currentSections as $currentSection) {
            if ($section = val(strtolower($currentSection), self::$sectionItems)) {
                $this->addSectionItems($section);
            }
        }
        return parent::prepare();
    }

    /**
     * @param array $section
     */
    public function addSectionItems($section) {
        if ($groups = val(self::GROUPS_INDEX, $section)) {
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

        if ($links = val(self::LINKS_INDEX, $section)) {
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
     */
    public function removeItem($key) {
        foreach (self::$sectionItems as &$section) {
            unset($section['links'][$key]);
        }
    }
}
