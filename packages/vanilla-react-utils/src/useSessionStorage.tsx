/*
 * @author Carla França <cfranca@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useEffect, useState } from "react";
import { getMeta } from "@library/utility/appUtils";

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

const host = getMeta("context.host", "");
const PREFIX = `vanilla/${host}`;

/**
 * Allow fetching and setting of a value in the user's sessionStorage.
 */
export function useSessionStorage<T>(key: string, defaultValue: T): [T, React.Dispatch<React.SetStateAction<T>>] {
    key = `${PREFIX}/${key}`;
    const [value, setValue] = useState(getStorageOrDefault(key, defaultValue));

    // This effect ensures memory state is pushed to session
    useEffect(() => {
        // Set the sessionStorage value
        sessionStorage.setItem(key, JSON.stringify(value));
    }, [key, value]);

    return [value, setValue];
}
