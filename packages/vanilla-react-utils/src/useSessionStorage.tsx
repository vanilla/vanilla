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
const SYNC_KEY = "syncSessionStorage";
const PAYLOAD_KEY = "sessionStorage";

/**
 * Allow fetching and setting of a value in the user's sessionStorage.
 */
export function useSessionStorage<T>(key: string, defaultValue: T): [T, React.Dispatch<React.SetStateAction<T>>] {
    key = `${PREFIX}/${key}`;
    const [value, setValue] = useState(getStorageOrDefault(key, defaultValue));

    // This effect ensures memory state is pushed to session and local storage
    useEffect(() => {
        // Set the sessionStorage value
        sessionStorage.setItem(key, JSON.stringify(value));
        // Update the localStorage
        localStorage.setItem(PAYLOAD_KEY, JSON.stringify(sessionStorage));
    }, [key, value]);

    /**
     * This function handles either setting the localStorage values when new tabs are opened
     * or setting the sessionStorage from localStorage if the local has been updated
     */
    const handleStorageSync = (storageEvent: StorageEvent) => {
        // New tab has requested session info
        if (storageEvent.key === SYNC_KEY) {
            // Fire a storage event with the content of the sessionStorage
            localStorage.setItem(PAYLOAD_KEY, JSON.stringify(sessionStorage));
        } else if (storageEvent.key === PAYLOAD_KEY && storageEvent.newValue) {
            const parsedNewValues = JSON.parse(storageEvent.newValue);
            // Rewrite the sessionStore
            Object.keys(parsedNewValues).forEach((key) => {
                sessionStorage.setItem(key, parsedNewValues[key]);
            });
            // Sync up the in memory state
            setValue(getStorageOrDefault(key, defaultValue));
        }
    };

    /**
     * TODO: Verify and fix sync between hub & nodes and node & node
     * https://higherlogic.atlassian.net/browse/VNLA-395
     */
    useEffect(() => {
        /**
         * This acts as a beacon, telling other tabs to commit their version of their
         * sessionStorage into localStorage so that the new tab can use the same values
         */
        if (!sessionStorage.length) {
            localStorage.setItem(SYNC_KEY, `${Date.now()}`);
        }

        window.addEventListener("storage", handleStorageSync);
        return () => {
            window.removeEventListener("storage", handleStorageSync);
        };
    }, []);

    return [value, setValue];
}
