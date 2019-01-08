/*!
 * @author Isis (igraziatto) Graziatto <isis.g@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

export function fireEvent(element, eventName, options) {
    var event = document.createEvent("CustomEvent");
    event.initCustomEvent(eventName, true, true, options);
    element.dispatchEvent(event);
}

export function toggleScroll() {
    if ($(document.body)[0].style.overflow) {
        enableScroll();
    } else {
        disableScroll();
    }
}

export function disableScroll() {
    $(document.body).addClass("NoScroll");
}

export function enableScroll() {
    $(document.body).removeClass("NoScroll");
}

/**
 * Provides requestAnimationFrame in a cross browser way.
 */

if (!window.requestAnimationFrame) {
    window.requestAnimationFrame = (function() {
        return (
            window.webkitRequestAnimationFrame ||
            window.mozRequestAnimationFrame ||
            window.oRequestAnimationFrame ||
            window.msRequestAnimationFrame ||
            function(
                /* function FrameRequestCallback */ callback,
                /* DOMElement Element */ element
            ) {
                window.setTimeout(callback, 1000 / 60);
            }
        );
    })();
}
