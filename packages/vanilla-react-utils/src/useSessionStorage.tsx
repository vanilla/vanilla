/*
 * @author Carla França <cfranca@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useState, useEffect } from "react";

/**
 * Get saved data from sessionStorage or DefaultValue
 */
function getStorageOrDefault<T>(key: string, defaultValue: T): T {
    const stored = sessionStorage.getItem(key);

    if (!stored) {
        return defaultValue;
    }
    return JSON.parse(stored);
}

const PREFIX = "vanilla";

/**
 * Allow fetching and setting of a value in the user's sesssionStorage.
 */
export function useSessionStorage<T>(key: string, defaultValue: T): [T, React.Dispatch<React.SetStateAction<T>>] {
    key = `${PREFIX}/${key}`;
    const [value, setValue] = useState(getStorageOrDefault(key, defaultValue));

    useEffect(() => {
        sessionStorage.setItem(key, JSON.stringify(value));
    }, [key, value]);

    return [value, setValue];
}
