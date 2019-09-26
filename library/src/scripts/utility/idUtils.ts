/**
 * Utilities and types for handling unique and/or required HTML ids in react components.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useMemo } from "react";

// Optional ID
export interface IOptionalComponentID {
    id?: string;
}

// Requires ID
export interface IRequiredComponentID {
    id: string;
}

const DEFAULT_PREFIX = "";

// Cache of ID's by their prefix.
let idCache: { [key: string]: number } = {};

// Generates unique ID from suffix
export function uniqueIDFromPrefix(prefix: string = DEFAULT_PREFIX) {
    let count = 0;

    if (prefix in idCache) {
        count = idCache[prefix] + 1;
    }
    idCache[prefix] = count;

    return prefix + "-" + idCache[prefix];
}

/**
 * React hook for useUniqueIDFromPrefix
 */
export function useUniqueID(prefix?: string) {
    return useMemo(() => uniqueIDFromPrefix(prefix), [prefix]);
}

/**
 * Clear the cache of unique IDs. Useful for testing particularly.
 */
export function clearUniqueIDCache() {
    idCache = {};
}

// Get required ID, will either return ID given through props or generate unique ID from suffix
export function getRequiredID(props: IOptionalComponentID, suffix: string): string {
    if (props.id) {
        return props.id;
    } else {
        return uniqueIDFromPrefix(suffix);
    }
}

// Get optional ID, will either return given ID through props if given, if true, will get generated ID from prefix, if false, ignored
export function getOptionalID(props: IOptionalComponentID, suffix?: string): string | null {
    if (props.id) {
        // we want an ID
        if (typeof props.id === "string") {
            // Handled by parent component
            return props.id;
        } else if (typeof props.id === "boolean" && suffix) {
            return uniqueIDFromPrefix(suffix);
        }
        throw new Error("To generate and ID, you must provide a suffix.");
    } else {
        return null;
    }
}
