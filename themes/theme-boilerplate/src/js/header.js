/*!
 * @author Isis (igraziatto) Graziatto <isis.g@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { fireEvent } from "./utility.js";

/**
 * Call this event on the window in order to collapse default collapsing elements.
 *
 * fireEvent(window, EVENT_COLLAPSE_DEFAULTS);
 */
const EVENT_COLLAPSE_DEFAULTS = "vanilla_collapse_defaults";

// Strings to represent the current state in a data-attribute
const STATE_CLOSED = "CLOSED";
const STATE_OPEN = "OPEN";
const RESIZE_THROTTLE_DURATION = 200;

export function setupHeader() {
    //initHeader();

    // Watch for window resizing and throttle the event listener
    var resizeTimer;
    $(window).resize(() => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(initHeader, 250);

        // Check if masonry is on the page and reload it
        const $tiles = $('body.Section-BestOf .masonry');
        if ($tiles.length > 0) {
            $tiles.masonry('reload');
        }
    });
}

function initHeader() {
    resetNavigation();
    initNavigationDropdown();
    initCategoriesModule();
    fireEvent(window, EVENT_COLLAPSE_DEFAULTS);
    initNavigationVisibility();
}

function initNavigationListeners() {
    const $navigation = $('#navdrawer');
    const className = 'isStuck';

    const setupListener = function setupListener() {
        const offset = $navigation.offset().top;

        $(window).on('scroll', () => {
            window.requestAnimationFrame(() => {
                if (!$navigation.hasClass(className) && $(window).scrollTop() >= offset) {
                    $navigation.addClass(className);
                } else if ($navigation.hasClass(className)) {
                    $navigation.removeClass(className);
                }
            })
        })
    }
}

/**
 * Initialize the mobile menu open/close listeners
 */
function initNavigationDropdown() {
    const $menuButton = $("#menu-button");
    const $menuList = $("#navdrawer");
    setupBetterHeightTransitions($menuList, $menuButton, true);
}

/**
 * Initialize the listeners for the accordian style categories module
 */
function initCategoriesModule() {
    const $children = $(".CategoriesModule-children");
    const $chevrons = $(".CategoriesModule-chevron");

    $chevrons.each((index, chevron) => {
        const $chevron = $(chevron);
        const $childList = $chevron
            .parent()
            .parent()
            .find(".CategoriesModule-children")
            .first();
        setupBetterHeightTransitions($childList, $chevron, true);
    });
}

/**
 * Hide the navigation menu so that it's not in the way as we calculate the sizes
 */
function resetNavigation() {
    const $nav = $("#navdrawer");
    resetBetterHeightTransition($nav);

    const $toggles = $("#menu-button.isToggled, #navdrawer .isToggled");
    $toggles.removeClass('isToggled');

    const $children = $(".CategoriesModule-children");
    $children.each((index, child) => {
        resetBetterHeightTransition($(child));
    })
}

/**
 * Show the navigation menu
 */
function initNavigationVisibility() {
    const $nav = $("#navdrawer");
    $nav.css({ position: "relative", visibility: "visible" });
    $nav.addClass('isReadyToTransition');
}

/**
 * Measure approximate real heights of an element and store/use it
 * to have a more accurate max-height transition.
 *
 * @param {any} $elementToMeasure
 * @param {any} toState
 */
function applyNewElementMeasurements($elementToMeasure, toState) {
    const trueHeight = $elementToMeasure.outerHeight() + "px";
    const previouslyCalculatedOldHeight = $elementToMeasure.attr(
        "data-true-height"
    );

    if (!previouslyCalculatedOldHeight) {
        $elementToMeasure.attr("data-true-height", trueHeight);
    }

    $elementToMeasure.attr("data-valid-open-state", false);

    if (toState === STATE_CLOSED) {
        $elementToMeasure.attr("data-valid-open-state", false);
        $elementToMeasure.css("overflow", "hidden");
        $elementToMeasure.css("max-height", "0px");
    } else if (toState === STATE_OPEN) {
        $elementToMeasure.attr("data-valid-open-state", true);
        $elementToMeasure.css(
            "max-height",
            $elementToMeasure.attr("data-true-height")
        );
        $elementToMeasure.on("transitionend", function handler() {
            if ($elementToMeasure.attr("data-valid-open-state") === "true") {
                $elementToMeasure.css("overflow", "visible");
                $elementToMeasure.off("transitionend", handler);
            }
        });
    }

    $elementToMeasure.attr("data-state", toState);
}

function resetBetterHeightTransition($element) {
    $element.removeClass('isReadyToTransition');
    $element.removeAttr('style');
    $element.removeAttr("data-true-height");
    $element.removeAttr("data-valid-open-state");
    $element.removeAttr("data-state");
}

/**
 * Setup a more accurate max-height transition on an element to be triggered by another element.
 *
 * @param {jquery.element} $elementToMeasure The jquery element to measure
 * @param {jquery.element} $triggeringElement The jquery element that triggers the transition
 * @param {boolean} collapseByDefault whether or not to collapse the element by default. This will happen after everything has been measured and you fire the EVENT_COLLAPSE_DEFAULTS from the window
 */
function setupBetterHeightTransitions(
    $elementToMeasure,
    $triggeringElement,
    collapseByDefault
) {
    applyNewElementMeasurements($elementToMeasure, STATE_OPEN);

    // Clear existing click listeners and then set them
    $triggeringElement.off();
    $triggeringElement.on("click", () => {
        const elementState = $elementToMeasure.attr("data-state");

        if (elementState === STATE_CLOSED) {
            $triggeringElement.toggleClass("isToggled");
            applyNewElementMeasurements($elementToMeasure, STATE_OPEN);
        } else if (elementState === STATE_OPEN) {
            $triggeringElement.toggleClass("isToggled");
            applyNewElementMeasurements($elementToMeasure, STATE_CLOSED);
        }
    });

    if (collapseByDefault) {
        window.addEventListener(EVENT_COLLAPSE_DEFAULTS, () => {
            applyNewElementMeasurements($elementToMeasure, STATE_CLOSED);
        });
    }
}
