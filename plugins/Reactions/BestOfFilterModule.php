<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license Proprietary
 */

/**
 * Renders the best of filter menu
 */
class BestOfFilterModule extends Gdn_Module {

    /**
     *
     *
     * @return string
     */
    public function assetTarget() {
        return 'Panel';
    }

    /**
     *
     *
     * @param $name
     * @param $code
     * @param $currentReactionType
     * @return string
     */
    private function button($name, $code, $currentReactionType) {
        $lCode = strtolower($code);
        $url = url("/bestof/$lCode");
        $cssClass = $code;
        if ($currentReactionType == $lCode) {
            $cssClass .= ' Active';
        }

        return '<li class="BestOf'.$cssClass.'"><a href="'.$url.'"><span class="ReactSprite React'.$code.'"></span> '.$name.'</a></li>';
    }

    /**
     *
     *
     * @return string
     */
    public function toString() {
        $controller = Gdn::controller();
        $currentReactionType = $controller->data('CurrentReaction');
        $reactionTypeData = $controller->data('ReactionTypes');
        $filterMenu = '<div class="BoxFilter BoxBestOfFilter"><ul class="FilterMenu">';

        $filterMenu .= $this->button(t('Everything'), 'Everything', $currentReactionType);
        foreach ($reactionTypeData as $key => $reactionType) {
            $filterMenu .= $this->button(t(val('Name', $reactionType, '')), val('UrlCode', $reactionType, ''), $currentReactionType);
        }

        $filterMenu .= '</ul></div>';
        return $filterMenu;
    }
}