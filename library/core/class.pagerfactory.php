<?php
/**
 * Gdn_PagerFactory.
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2015 Vanilla Forums Inc.
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
     * @param $PagerType
     * @param $Sender
     * @return bool
     */
    public function getPager($PagerType, $Sender) {
        $PagerType = $PagerType.'Module';

        if (!class_exists($PagerType)) {
            $PagerType = 'PagerModule';
        }

        if (!class_exists($PagerType)) {
            return false;
        }

        return new $PagerType($Sender);
    }
}
