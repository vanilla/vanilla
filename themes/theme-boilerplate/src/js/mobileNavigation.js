/*!
 * @author Isis (igraziatto) Graziatto <isis.g@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

export function setupMobileNavigation() {

    var $menuButton = $("#menu-button"),
        $navdrawer = $("#navdrawer"),
        $mainHeader = $("#MainHeader");

    $menuButton.on("click", () => {
        $menuButton.toggleClass("isToggled");
        $navdrawer.toggleClass("isOpen");
        $mainHeader.toggleClass("hasOpenNavigation");
    });
}
