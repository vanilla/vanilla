/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

import { polyfillClosest } from "../entries/polyfills";
import "mutationobserver-shim";
// import Enzyme from "enzyme";
// import Adapter from "enzyme-adapter-react-16";

// Setup enzyme
// Enzyme.configure({ adapter: new Adapter() });

// Because there is something weird happening in watch mode if this gets applied twice.
// Likely the JSDOM references get deleted and need to be referenced.
if (Element.prototype.closest) {
    delete Element.prototype.closest;
}

// Because JSDOM doesn't support this yet
// https://github.com/facebook/jest/issues/2028
polyfillClosest();

function getSelection() {
    return {
        removeAllRanges: () => {
            return;
        },
        getRangeAt: () => {
            return;
        }
    };
}

document.getSelection = getSelection as any;
