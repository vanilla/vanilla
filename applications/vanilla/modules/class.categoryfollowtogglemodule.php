<?php
/**
 * Category Follow Toggle module
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Vanilla
 * @since 2.0
 */

/**
 * Allows the user to show all unfollowed categories so they can re-follow them.
 *
 * @deprecated 2.2.113 Dropped in favor of muting categories
 */
class CategoryFollowToggleModule extends Gdn_Module {

    /**
     * Set the preference in the user's session.
     */
    public function setToggle() {
        $session = Gdn::session();
        if (!$session->isValid()) {
            return;
        }

        $showAllCategories = getIncomingValue('ShowAllCategories', '');
        if ($showAllCategories != '') {
            $showAllCategories = $showAllCategories == 'true' ? true : false;
            $showAllCategoriesPref = $session->getPreference('ShowAllCategories');
            if ($showAllCategories != $showAllCategoriesPref) {
                $session->setPreference('ShowAllCategories', $showAllCategories);
            }

            redirectTo('/'.ltrim(Gdn::request()->path(), '/'));
        }
    }

    public function assetTarget() {
        return 'Panel';
    }

    public function toString() {
        return '';
    }
}
