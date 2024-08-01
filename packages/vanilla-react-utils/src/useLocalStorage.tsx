/*
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useEffect, useState } from "react";
import { getMeta } from "@library/utility/appUtils";

/**
 * Get saved data from localStorage or DefaultValue
 */
export function getStorageOrDefault<T>(key: string, defaultValue: T): T {
    const stored = localStorage.getItem(key);

    if (!stored) {
        return defaultValue;
    }
    return JSON.parse(stored);
}

const host = getMeta("context.host", "");
const PREFIX = `vanilla/${host}`;

/**
 * Allow fetching and setting of a value in the user's local storage.
 */
export function useLocalStorage<T>(key: string, defaultValue: T): [T, React.Dispatch<React.SetStateAction<T>>] {
    key = `${PREFIX}/${key}`;
    const [value, setValue] = useState(getStorageOrDefault(key, defaultValue));

    // This effect ensures memory state is pushed to local storage
    useEffect(() => {
        // Set the localStorage value
        localStorage.setItem(key, JSON.stringify(value));
    }, [key, value]);

    return [value, setValue];
}
