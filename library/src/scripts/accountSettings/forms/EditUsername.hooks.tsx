/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import apiv2 from "@library/apiv2";
import React, { useCallback, useEffect, useState } from "react";

/**
 * This hook will check if a given user name is available
 */
export function useUsernameAvailability(username: string): boolean {
    // Cache past searches
    const [availabilityCache, setCache] = useState<Record<string, boolean>>({});

    // Check if the name exists on the server
    const checkUsername = useCallback(async (name: string) => {
        const { data } = await apiv2.get("/users/by-names", {
            params: {
                name: name,
            },
        });
        // If there are no users returned from the API, the name should be available
        setCache((prevValue) => {
            return {
                ...prevValue,
                [name]: Array.isArray(data) && data.length < 1,
            };
        });
    }, []);

    // Fetch from server if its not in cache
    useEffect(() => {
        // Checking the key here instead of value because
        // we storing booleans and JS handles falsy values in the same way
        if (username && !Object.keys(availabilityCache).includes(username)) {
            checkUsername(username);
        }
    }, [username, checkUsername]);

    return availabilityCache[username];
}
