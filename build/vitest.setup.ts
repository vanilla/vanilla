import { expect, afterEach } from "vitest";
import { cleanup as cleanupReactTesting } from "@testing-library/react";
import "@testing-library/jest-dom/vitest";
import { EMPTY_RECT } from "@vanilla/react-utils";
import { mockAPI } from "@library/__tests__/utility";
import MockAdapter from "axios-mock-adapter";
import Axios from "axios";
import { cleanup as cleanupReactHooks } from "@testing-library/react-hooks";
import { resetGlobalValues } from "@vanilla/utils";
import "vitest-canvas-mock";

let mockApi: MockAdapter;

beforeAll(() => {
    // Always override the global axios in case something uses it.
    new MockAdapter(Axios);
    mockApi = mockAPI();
});

afterAll(() => {
    mockApi.reset();
    resetGlobalValues();
});

beforeEach(() => {
    // @ts-ignore
    delete window.location;
    window.location = new URL("http://localhost") as any;
});

afterEach(() => {
    cleanupReactTesting();
    void cleanupReactHooks();
});

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

(global as any).IntersectionObserver = class IntersectionObserver {
    constructor() {}

    observe() {
        // do nothing
    }

    disconnect() {
        // do nothing
    }

    unobserve() {
        // do nothing
    }
};

// Mock DataTransfer, commonly used in copy/paste and drag/drop operations.
(global as any).DataTransfer = class DataTransfer {
    private data!: any;
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

    if (args[0]?.includes("componentWillReceiveProps has been renamed")) {
        // React router 5 warnings.
        return;
    }
    originalWarn(...args);
};

const originalError = console.error;
global.console.error = (...args) => {
    for (const arg of args) {
        if (typeof arg === "string") {
            if (
                arg.includes("state update on an unmounted component") ||
                arg.includes("Could not parse CSS stylesheet")
            )
                return;
        }
    }

    originalError(...args);
};

Object.defineProperty(window, "matchMedia", {
    writable: true,
    value: vi.fn().mockImplementation((query) => ({
        matches: false,
        media: query,
        onchange: null,
        addListener: vi.fn(), // deprecated
        removeListener: vi.fn(), // deprecated
        addEventListener: vi.fn(),
        removeEventListener: vi.fn(),
        dispatchEvent: vi.fn(),
    })),
});
