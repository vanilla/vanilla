/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

const { EMPTY_RECT } = require("@vanilla/react-utils");
const enzyme = require("enzyme");
const Adapter = require("enzyme-adapter-react-16");
const registerRequireContextHook = require("babel-plugin-require-context-hook/register");
require("@testing-library/jest-dom/extend-expect");

enzyme.configure({ adapter: new Adapter() });
registerRequireContextHook();

// Mock resize observer
global.ResizeObserver = class ResizeObserver {
    observe() {
        // do nothing
    }
    unobserve() {
        // do nothing
    }
    disconnect() {
        // do nothing
    }
};

// Mock DataTransfer, commonly used in copy/paste and drag/drop operations.
global.DataTransfer = class DataTransfer {
    constructor() {
        this.data = new Map();
    }

    getData = (key) => this.data.get(key);
    setData = (key, val) => this.data.set(key, val);
};

global.Range.prototype.getBoundingClientRect = () => {
    // Make these a bit different from the empty rect so that things checking that something actually rendered
    // see an actual rect. Some of our code has bailouts on an empty rect.
    return { ...EMPTY_RECT, top: 1, left: 1, width: 20, height: 20 };
};
const originalWarn = console.warn;
global.console.warn = (...args) => {
    if (args[0]?.startsWith("@reach")) {
        // Suppress reach style warnings.
        return;
    }
    originalWarn(...args);
};

// Stub in require.context (does nothing).
if (!global.require) {
    global.require = () => {};
}
global.require.context = (function () {
    function req() {}
    req.keys = function () {
        return [];
    };
    req.resolve = function () {};
    return req;
})();

jest.setTimeout(10000);
