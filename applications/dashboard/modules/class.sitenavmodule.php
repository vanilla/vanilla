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
 * Module for a list of links.
 */
class SiteNavModule extends NavModule {

    const SECTION_GLOBAL = 'globals';
    const SECTION_DEFAULT = 'defaults';

    const LINKS_INDEX = 'links';
    const GROUPS_INDEX = 'groups';

    /** @var array  */
    protected static $sectionItems = [];
    protected static $initStaticFired = false;

    public function addLinkToSection($section, $text, $url, $key = '', $cssClass = '', $sort = [], $modifiers = [], $disabled = false) {
        $args = func_get_args();
        self::$sectionItems[strtolower($section)][self::LINKS_INDEX][] = $args;
        return $this;
    }

    public function addLinkToSectionIf($isAllowed, $section, $text, $url, $key = '', $cssClass = '', $sort = [], $modifiers = [], $disabled = false) {
        if (!$this->isAllowed($isAllowed)) {
            return $this;
        } else {
            return $this->addLinkToSection($section, $text, $url, $key, $cssClass, $sort, $modifiers, $disabled);
        }
    }

    public function addGroupToSection($section, $text = '', $key = '', $cssClass = '', $sort = [], $modifiers = []) {
        $args = func_get_args();
        self::$sectionItems[strtolower($section)][self::GROUPS_INDEX][] = $args;
        return $this;
    }

    public function addGroupToSectionIf($isAllowed, $section, $text = '', $key = '', $cssClass = '', $sort = [], $modifiers = []) {
        if (!$this->isAllowed($isAllowed)) {
            return $this;
        } else {
            return $this->addGroupToSection($section, $text, $key, $cssClass, $sort, $modifiers);
        }
    }

    public function addLink($text, $url, $key = '', $cssClass = '', $sort = [], $modifiers = [], $disabled = false) {
        $this->addLinkToSection(self::SECTION_DEFAULT, $text, $url, $key, $cssClass, $sort, $modifiers, $disabled);
        return $this;
    }

    public function addLinkIf($isAllowed, $text, $url, $key = '', $cssClass = '', $sort = [], $modifiers = [], $disabled = false) {
        if (!$this->isAllowed($isAllowed)) {
            return $this;
        } else {
            return $this->addLink($text, $url, $key, $cssClass, $sort, $modifiers, $disabled);
        }
    }

    public function addGroup($text = '', $key = '', $cssClass = '', $sort = [], $modifiers = []) {
        $this->addGroupToSection(self::SECTION_DEFAULT, $text, $key, $cssClass, $sort, $modifiers);
        return $this;
    }

    public function addGroupIf($isAllowed, $text = '', $key = '', $cssClass = '', $sort = [], $modifiers = []) {
        if (!$this->isAllowed($isAllowed)) {
            return $this;
        } else {
            return $this->addGroup($text, $key, $cssClass, $sort, $modifiers);
        }
    }

    public function addLinkToGlobals($text, $url, $key = '', $cssClass = '', $sort = [], $modifiers = [], $disabled = false) {
        $this->addLinkToSection(self::SECTION_GLOBAL, $text, $url, $key, $cssClass, $sort, $modifiers, $disabled);
        return $this;
    }

    public function addLinkToGlobalsIf($isAllowed = true, $text, $url, $key = '', $cssClass = '', $sort = [], $modifiers = [], $disabled = false) {
        if (!$this->isAllowed($isAllowed)) {
            return $this;
        } else {
            return $this->addLinkToGlobals($text, $url, $key, $cssClass, $sort, $modifiers, $disabled);
        }
    }

    public function addGroupToGlobals($text = '', $key = '', $cssClass = '', $sort = [], $modifiers = []) {
        $this->addGroupToSection(self::SECTION_GLOBAL, $text, $key, $cssClass, $sort, $modifiers);
        return $this;
    }

    public function addGroupToGlobalsIf($isAllowed, $text = '', $key = '', $cssClass = '', $sort = [], $modifiers = []) {
        if (!$this->isAllowed($isAllowed)) {
            return $this;
        } else {
            return $this->addGroupToGlobals($text, $key, $cssClass, $sort, $modifiers);
        }
    }

    /**
     *
     *
     * @throws Exception
     */
    public function prepare() {

        if (!self::$initStaticFired) {
            self::$initStaticFired = true;
            $this->fireEvent('init');
        }

        $currentSections = Gdn_Theme::section('', 'get');
        $currentSections = array_map('strtolower', $currentSections);


        if (!empty(array_intersect(array_keys(self::$sectionItems), $currentSections))) {
            $customMenu = true;
        }

        if (!$customMenu) {
            $currentSections = [self::SECTION_DEFAULT];
        }

        // Add global items
        $currentSections[] = self::SECTION_GLOBAL;

        foreach ($currentSections as &$currentSection) {
            if ($section = val(strtolower($currentSection), self::$sectionItems)) {
                $this->addSectionItems($section);
            }
        }
        return parent::prepare();
    }

    public function addSectionItems($section) {
        if ($groups = val(self::GROUPS_INDEX, $section)) {
            foreach ($groups as $group) {
                parent::addGroup($group[1], $group[2]);
            }
        }

        if ($links = val(self::LINKS_INDEX, $section)) {
            foreach ($links as $link) {
                parent::addLink($link[1], $link[2], $link[3], $link[4], $link[5], $link[6], $link[7]);
            }
        }
    }
}
