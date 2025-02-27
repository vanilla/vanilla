/*
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useEffect, useState } from "react";
import { getMeta } from "@library/utility/appUtils";

const host = getMeta("context.host", "");
const PREFIX = `vanilla/${host}`;

/**
 * Get saved data from localStorage or DefaultValue
 */
export function getLocalStorageOrDefault<T>(key: string, defaultValue: T, includePrefix = false): T {
    const stored = localStorage.getItem(includePrefix ? `${PREFIX}/${key}` : key);

    if (!stored) {
        return defaultValue;
    }
    return JSON.parse(stored);
}

/**
 * Allow fetching and setting of a value in the user's local storage.
 */
export function useLocalStorage<T>(key: string, defaultValue: T): [T, React.Dispatch<React.SetStateAction<T>>] {
    key = `${PREFIX}/${key}`;
    const [value, setValue] = useState(getLocalStorageOrDefault(key, defaultValue));

    // This effect ensures memory state is pushed to local storage
    useEffect(() => {
        // Set the localStorage value
        try {
            localStorage.setItem(key, JSON.stringify(value));
        } catch (error) {
            console.error("Error setting localStorage", error);
        }
    }, [key, value]);

    return [value, setValue];
}
