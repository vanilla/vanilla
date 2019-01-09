<?php
/**
 * Gdn_PagerFactory.
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
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
