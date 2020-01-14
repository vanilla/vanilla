<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures;

/**
 * A mock new discussion module to test NewDiscussionModule methods.
 */

class MockNewDiscussionModule {
    /**
     * @var array Buttons array
     */
    private $buttons = [];

    /**
     * @var bool Whether to reorder HTML.
     */
    private $reorder = false;

    /**
     * Add a button to the collection.
     *
     * @param string $text
     * @param string $url
     * @param bool $asOwnButton Whether to display as a separate button or not.
     */
    public function addButton($text, $url, $asOwnButton) {
        $this->buttons[] = ['Text' => $text, 'Url' => $url, 'asOwnButton' => $asOwnButton];
    }

    /**
     * Groups buttons according to whether they are standalone or part of a dropdown.
     *
     * @return array Returns buttons grouped by whether they are standalone or part of a dropdown.
     */
    public function getButtonGroups() {
        $this->reorder = true;
        $allButtons = [];
        $groupedButtons = [];
        foreach ($this->buttons as $key => $button) {
            if ($button['asOwnButton']) {
                $allButtons[] = [$button];
            } else {
                $groupedButtons[] = $button;
            }
        }
        if (!empty($groupedButtons)) {
            array_unshift($allButtons, $groupedButtons);
        }
        return $allButtons;
    }
}
