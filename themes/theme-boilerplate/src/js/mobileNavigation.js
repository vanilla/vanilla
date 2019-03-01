/*!
 * @author Isis (igraziatto) Graziatto <isis.g@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

const INIT_CLASS = "needsInitialization";
const CALC_HEIGHT_ATTR = "data-height";
const COLLAPSED_HEIGHT = "0px";

export function setupMobileNavigation() {

    const menuButton = document.querySelector("#menu-button");
    /** @type {HTMLElement} */
    const navdrawer = document.querySelector(".js-nav");
    /** @type {HTMLElement} */
    const mobileMebox = document.querySelector(".js-mobileMebox");
    const mobileMeBoxBtn = document.querySelector(".mobileMeBox-button");
    const mobileMeboxBtnClose = document.querySelector(".mobileMebox-buttonClose");
    const mainHeader = document.querySelector("#MainHeader");

    // Calculate the values initially.
    prepareElement(mobileMebox);
    prepareElement(navdrawer);

    // Update the calculated values on resize.
    window.addEventListener("resize", () => {
        requestAnimationFrame(() => {
            prepareElement(mobileMebox);
            prepareElement(navdrawer);
        })
    })

    menuButton.addEventListener("click", () => {
        menuButton.classList.toggle("isToggled");
        mainHeader.classList.toggle("hasOpenNavigation");
        collapseElement(mobileMebox);
        toggleElement(navdrawer);
    });

    mobileMeBoxBtn && mobileMeBoxBtn.addEventListener("click", () => {
        mobileMeBoxBtn.classList.toggle("isToggled");
        mainHeader.classList.remove("hasOpenNavigation");
        menuButton.classList.remove("isToggled");
        collapseElement(navdrawer)
        toggleElement(mobileMebox);
    });

    mobileMeboxBtnClose && mobileMeboxBtnClose.addEventListener("click", () => {
        collapseElement(mobileMebox);
    });

    /**
     * @param {HTMLElement} element
     */
    function toggleElement(element) {
        if (element.style.height === COLLAPSED_HEIGHT) {
            expandElement(element);
        } else {
            collapseElement(element);
        }
    }

    /**
     * @param {HTMLElement} element
     */
    function collapseElement(element) {
        element.style.height = COLLAPSED_HEIGHT;
    }

    /**
     *
     * @param {HTMLElement} element
     */
    function expandElement(element) {
        element.style.height = element.getAttribute(CALC_HEIGHT_ATTR) + "px";
    }

    /**
     * Get the calculated height of an element and
     *
     * @param {HTMLElement} element
     */
    function prepareElement(element) {
        element.classList.add(INIT_CLASS);
        element.style.height = "auto";
        const calcedHeight = element.getBoundingClientRect().height;

        // Visual hide the element.
        element.setAttribute(CALC_HEIGHT_ATTR, calcedHeight.toString());
        collapseElement(element);
        element.classList.remove(INIT_CLASS);
    }
}
