/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

export function globalValueRef<T>(key: string, initialValue: T) {
    if (!globalThis.__VANILLA_GLOBALS__) {
        globalThis.__VANILLA_GLOBALS__ = {};
    }
    if (!(key in globalThis.__VANILLA_GLOBALS__)) {
        globalThis.__VANILLA_GLOBALS__[key] = initialValue;
    }
    return {
        current: (): T => {
            return globalThis.__VANILLA_GLOBALS__[key];
        },
        set: (value: T) => {
            globalThis.__VANILLA_GLOBALS__[key] = value;
        },
    };
}

export function resetGlobalValues() {
    globalThis.__VANILLA_GLOBALS__ = {};
}
