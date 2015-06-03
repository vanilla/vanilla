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
    public function SetToggle() {
        $Session = Gdn::Session();
        if (!$Session->IsValid())
            return;

        $ShowAllCategories = getIncomingValue('ShowAllCategories', '');
        if ($ShowAllCategories != '') {
            $ShowAllCategories = $ShowAllCategories == 'true' ? TRUE : FALSE;
            $ShowAllCategoriesPref = $Session->GetPreference('ShowAllCategories');
            if ($ShowAllCategories != $ShowAllCategoriesPref)
                $Session->SetPreference('ShowAllCategories', $ShowAllCategories);

            Redirect('/'.ltrim(Gdn::Request()->Path(), '/'));
        }
    }

    public function AssetTarget() {
        return 'Panel';
    }

    public function ToString() {
        if (Gdn::Session()->IsValid())
            return parent::ToString();

        return '';
    }
}
