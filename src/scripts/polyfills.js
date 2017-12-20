/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

// @ts-nocheck

import Promise from "promise-polyfill";
import setAsap from "setasap";

import * as utility from "@core/utility";

/**
 * Polyfills core-js and a few additional things.
 *
 * Feel free to add additional polyfills here if they're simple,
 * but only functionality present in the latest major version of Chrome, Firefox, and Safari
 * should be polyfilled.
 *
 * @returns {Promise<any>} - A Promise that resolves when all polyfills have been loaded.
 */
export default function loadPolyfills() {

    /**
     * We need to polyfill promises in order to use webpack's dynamic imports
     * to polyfill the other feature. It's small so ¯\_(ツ)_/¯.
     */
    if (!window.Promise) {
        Promise._immediateFn = setAsap;
        window.Promise = Promise;
    }

    /**
     * Polyfill forEach on NodeList.
     *
     * This can be removed once v3 of core-js is released
     * https://github.com/zloirock/core-js/issues/329.
     *
     * Polyfill included here is taken from Mozilla.
     * https://developer.mozilla.org/en-US/docs/Web/API/NodeList/forEach#Polyfill
     */
    function polyfillNodeListforEach() {
        if (window.NodeList && !NodeList.prototype.forEach) {
            NodeList.prototype.forEach = function forEach (callback, thisArg) {
                thisArg = thisArg || window;
                for (let i = 0; i < this.length; i++) {
                    callback.call(thisArg, this[i], i, this);
                }
            };
        }
    }

    /**
     * Polyfill Element.closest() on IE 9+.
     *
     * This is currently outside of the scope of core-js.
     * https://github.com/zloirock/core-js/issues/317
     *
     * Polyfill included here is taken from Mozilla.
     * https://developer.mozilla.org/en-US/docs/Web/API/Element/closest#Polyfill
     */
    function polyfillClosest() {
        if (!Element.prototype.matches) {
            Element.prototype.matches = Element.prototype.msMatchesSelector || Element.prototype.webkitMatchesSelector;
        }

        if (!Element.prototype.closest) {
            Element.prototype.closest = function closest(s) {
                let el = this;
                if (!document.documentElement.contains(el)) {
                    return null;
                }
                do {
                    if (el.matches(s)) {
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
     * Dynamically import a core-js polyfill.
     *
     * @returns {Promise<any>} - A Promise that resolves when core JS has been loaded.
     */
    function polyfillCoreJs() {
        return import(/* webpackChunkName: "polyfill" */ "babel-polyfill")
            .then(() => {
                utility.log("Loading polyfills");
            })
            .catch(e => {
                utility.log(e);
            });
    }

    return Promise.all([polyfillNodeListforEach(), polyfillCoreJs(), polyfillClosest(), polyfillRemove()]);
}
