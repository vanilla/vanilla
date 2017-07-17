<?php
/**
 * Category Follow Toggle module
 *
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
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
        $Session = Gdn::session();
        if (!$Session->isValid()) {
            return;
        }

        $ShowAllCategories = GetIncomingValue('ShowAllCategories', '');
        if ($ShowAllCategories != '') {
            $ShowAllCategories = $ShowAllCategories == 'true' ? true : false;
            $ShowAllCategoriesPref = $Session->GetPreference('ShowAllCategories');
            if ($ShowAllCategories != $ShowAllCategoriesPref) {
                $Session->setPreference('ShowAllCategories', $ShowAllCategories);
            }

            redirectTo('/'.ltrim(Gdn::request()->Path(), '/'));
        }
    }

    public function assetTarget() {
        return 'Panel';
    }

    public function toString() {
        return '';
    }
}
