<?php
/**
 * Site nav module.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
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

    /**
     *
     *
     * @throws Exception
     */
    public function render() {
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
            $this->fireEvent('default');
        }

        // Fire an event for everything.
        $this->fireEvent('all');

        parent::render();
    }
}
