<?php
/**
 * Category Follow Toggle module
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Vanilla
 * @since 2.0
 */

/**
 * Allows the user to show all unfollowed categories so they can re-follow them.
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

            redirect('/'.ltrim(Gdn::request()->Path(), '/'));
        }
    }

    public function assetTarget() {
        return 'Panel';
    }

    public function toString() {
        if (Gdn::session()->isValid()) {
            return parent::ToString();
        }

        return '';
    }
}
