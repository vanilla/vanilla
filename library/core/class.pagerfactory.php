<?php
/**
 * Gdn_PagerFactory.
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

/**
 * Handles creating and returning a pager.
 */
class Gdn_PagerFactory {

    /**
     *
     *
     * @param $pagerType
     * @param $sender
     * @return bool
     */
    public function getPager($pagerType, $sender) {
        $pagerType = $pagerType.'Module';

        if (!class_exists($pagerType)) {
            $pagerType = 'PagerModule';
        }

        if (!class_exists($pagerType)) {
            return false;
        }

        return new $pagerType($sender);
    }
}
