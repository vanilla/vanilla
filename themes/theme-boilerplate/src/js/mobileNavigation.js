/*!
 * @author Isis (igraziatto) Graziatto <isis.g@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

export function setupMobileNavigation() {

    var $menuButton = $("#menu-button"),
        $navdrawer = $("#navdrawer"),
        $mobileMebox = $(".js-mobileMebox"),
        $mobileMeBoxBtn = $(".mobileMeBox-button"),
        $mobileMeboxBtnClose = $(".mobileMebox-buttonClose"),
        $mainHeader = $("#MainHeader");

    $menuButton.on("click", () => {
        $menuButton.toggleClass("isToggled");
        $navdrawer.toggleClass("isOpen");
        $mainHeader.toggleClass("hasOpenNavigation");
        $mobileMebox.removeClass("isOpen");
    });

    $mobileMeBoxBtn.on("click", () => {
        $mobileMeBoxBtn.toggleClass("isToggled");
        $mobileMebox.toggleClass("isOpen");
        $mainHeader.removeClass("hasOpenNavigation");
        $menuButton.removeClass("isToggled");
        $navdrawer.removeClass("isOpen");
    });

    $mobileMeboxBtnClose.on("click", () => {
        $mobileMebox.removeClass("isOpen");
    });
}
