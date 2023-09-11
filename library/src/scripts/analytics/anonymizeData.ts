/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import apiv2 from "@library/apiv2";
import { hasPermission } from "@library/features/users/Permission";
import { getMeta } from "@library/utility/appUtils";

export const META_KEY_ANONYMIZE = "AnonymizeData";
export enum AnonymizeOptions {
    TRUE = "1",
    FALSE = "0",
    DEFAULT = "-1",
}

/**
 * Get the user preference to anonymize data for analytics.
 * If the user has not set their preference, return the site's setting as the default.
 *
 * @returns Promise<boolean>
 */
export async function getAnonymizeData(): Promise<boolean> {
    const defaultValue = getMeta(META_KEY_ANONYMIZE, false);

    // User is logged in. Check the API for preference
    if (hasPermission("session.valid")) {
        try {
            const result = await apiv2.get("/analytics/privacy");
            if (result.data.AnonymizeData === AnonymizeOptions.DEFAULT) {
                return defaultValue;
            }
            return result.data.AnonymizeData === AnonymizeOptions.TRUE;
        } catch (error) {
            return defaultValue;
        }
    }

    const cookieData = await getCookieData();
    const cookieName = cookieData["garden.cookie.name"] + "-" + META_KEY_ANONYMIZE;
    // User is not logged in. Check cookies for preference
    const rawCookies = decodeURIComponent(document.cookie).split("; ");
    const bakeCookies = Object.fromEntries(rawCookies.map((cky) => cky.split("=")));
    // Cookie does not exist, use site default
    return bakeCookies[cookieName] === undefined ? defaultValue : bakeCookies[cookieName] === "true";
}

/**
 * Set the user preference to anonymize data for analytics.
 *
 * @param anonymize boolean Optional value to set. If not provided, then default site setting will be used.
 * @returns Promise<boolean>
 */
export async function setAnonymizeData(anonymize?: boolean): Promise<boolean> {
    const enumKey = anonymize === undefined ? "DEFAULT" : anonymize.toString().toUpperCase();
    const defaultValue = getMeta(META_KEY_ANONYMIZE, false);

    // User is logged in. Save the preference to the API.
    if (hasPermission("session.valid")) {
        try {
            const result = await apiv2.post("/analytics/privacy", {
                AnonymizeData: AnonymizeOptions[enumKey],
            });
            if (result.data.AnonymizeData === AnonymizeOptions.DEFAULT) {
                return defaultValue;
            }
            return result.data.AnonymizeData === AnonymizeOptions.TRUE;
        } catch (error) {
            return defaultValue;
        }
    }

    const cookieData = await getCookieData();
    const cookieName = cookieData["garden.cookie.name"] + "-" + META_KEY_ANONYMIZE;
    const cookiePath = cookieData["garden.cookie.path"];
    // User is not logged in. Save the preference to a cookie.
    const newValue = anonymize === undefined ? defaultValue : anonymize;
    document.cookie = [cookieName, newValue].join("=") + `;path=${cookiePath}`;
    return newValue;
}

export async function getCookieData(): Promise<string> {
    const configKey = "garden.cookie.name";
    const pathKey = "garden.cookie.path";
    const config = await apiv2.get(`/config?select=${configKey},${pathKey}`);
    return config.data;
}
