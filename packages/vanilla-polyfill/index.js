/**
 * Polyfill entrypoint. This can't have a dependency on
 * polyfilled functionality before the polyfills are active.
 *
 * @see {AssetModel::getInlinePolyfillJSContent()} for how the build polyfill entry gets added to the page.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import "core-js/stable";
import "@webcomponents/webcomponentsjs";

polyfillClosest();
polyfillRemove();
polyfillCustomEvent();
polyfillStringNormalize();

/**
 * Polyfill Element.closest() on IE 9+.
 *
 * This is currently outside of the scope of core-js.
 * https://github.com/zloirock/core-js/issues/317
 *
 * Polyfill included here is taken from Mozilla.
 * https://developer.mozilla.org/en-US/docs/Web/API/Element/closest#Polyfill
 */
export function polyfillClosest() {
    if (!Element.prototype.matches) {
        Element.prototype.matches =
            (Element).prototype.msMatchesSelector || (Element).prototype.webkitMatchesSelector;
    }

    if (!Element.prototype.closest) {
        Element.prototype.closest = function closest(s) {
            let el = this;
            if (document.documentElement && !document.documentElement.contains(el)) {
                return null;
            }
            do {
                if (el instanceof Element && el.matches && el.matches(s)) {
                    return el;
                }
                el = el.parentElement || el.parentNode;
            } while (el !== null);
            return null;
        };
    }
}

/**
 * Polyfill Element.remove() on IE 9+.
 *
 * This is currently outside of the scope of core-js.
 * https://github.com/zloirock/core-js/issues/317
 *
 * Polyfill included here is taken from Mozilla.
 * https://developer.mozilla.org/en-US/docs/Web/API/ChildNode/remove#Polyfill
 */
function polyfillRemove() {
    (arr => {
        arr.forEach(item => {
            if (item.hasOwnProperty("remove")) {
                return;
            }
            Object.defineProperty(item, "remove", {
                configurable: true,
                enumerable: true,
                writable: true,
                value: function remove() {
                    if (this.parentNode !== null) {
                        this.parentNode.removeChild(this);
                    }
                },
            });
        });
    })([Element.prototype, CharacterData.prototype, DocumentType.prototype]);
}

/**
 * Fixes CustomEvent in IE 9-11
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/API/CustomEvent/CustomEvent
 */
function polyfillCustomEvent() {
    if (typeof window.CustomEvent === "function") {
        return;
    }

    function CustomEvent(event, params) {
        params = params || { bubbles: false, cancelable: false, detail: undefined };
        const evt = document.createEvent("CustomEvent");
        evt.initCustomEvent(event, params.bubbles, params.cancelable, params.detail);
        return evt;
    }

    CustomEvent.prototype = window.Event.prototype;

    window.CustomEvent = CustomEvent;
}

/**
 * A library like unorm would be a lot more robust but is huge!
 * Users of older browsers just don't get full string normalization.
 */
function polyfillStringNormalize() {
    if (typeof String.prototype.normalize === "function") {
        return;
    }

    String.prototype.normalize = function () {
        return this;
    }
}
