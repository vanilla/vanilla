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

    /** @var array  */
    protected $customSections = array('EditProfile', 'Profile');

    /** @var string The section to */
    protected $section;

    protected $defaultEvent = 'default';

    public function __construct() {
        parent::__construct();
    }

    /**
     * @return string
     */
    public function getSection() {
        return $this->section;
    }

    /**
     * @param string $section
     * @return $this
     */
    public function setSection($section) {
        $this->section = $section;
        return $this;
    }

    /**
     *
     *
     * @throws Exception
     */
    public function prepare() {
        $section_found = false;

        // The module contains different links depending on its section.
        foreach ($this->customSections as $section) {
            if (InSection($section)) {
                $this->fireEvent($section);
                $section_found = true;
                break;
            }
        }

        // If a section wasn't found then add the default nav.
        if (!$section_found) {
            $this->fireEvent($this->defaultEvent);
        }

        // Fire an event for everything.
        $this->fireEvent('all');

        return parent::prepare();
    }
}
