/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useContext, useEffect, useState } from "react";
import qs from "qs";
import { formatUrl, getMeta, siteUrl } from "@library/utility/appUtils";

// The name of the custom event that we fire when an update happens.
const EVENT_NAME = "X-Apply-Entry-Link-Parameters";

interface IEntryLinkContext {
    [key: string]: any;
}

const context = React.createContext<IEntryLinkContext>({});

/**
 * Context provider for applying arbitrary query parameters for entry urls.
 */
export const EntryLinkContextProvider = (props: { children: React.ReactNode }) => {
    const [linkParams, setLinkParams] = useState({});

    useEffect(() => {
        // Listen for the event that occurs when contents are updated.
        const handler = (e: CustomEvent) => {
            setLinkParams(e.detail);
        };

        document.addEventListener(EVENT_NAME, handler);

        return () => {
            document.removeEventListener(EVENT_NAME, handler);
        };
    }, []);

    return <context.Provider {...props} value={linkParams} />;
};

/**
 * Get the context value.
 */
export function useEntryLinkContext() {
    return useContext(context);
}

/**
 * Update the current query parameters contexts.
 *
 * @param newParameters
 */
export function applyEntryLinkParameters(newParameters: IEntryLinkContext) {
    document.dispatchEvent(new CustomEvent(EVENT_NAME, { detail: newParameters }));
}

/**
 * Get the current signin link.
 */
export function useSignInLink(): string {
    const contextQuery = useEntryLinkContext();
    const metaUrl = getMeta("signInUrl", "/entry/signin");
    return makeContextualAuthLink(metaUrl, contextQuery);
}

/**
 * Get the current register link.
 */
export function useRegisterLink(): string | null {
    const contextQuery = useEntryLinkContext();
    const metaUrl = getMeta("registrationUrl", "/entry/register");

    if (!metaUrl) {
        // We don't have a register URL.
        return null;
    }

    return makeContextualAuthLink(metaUrl, contextQuery);
}

/**
 * Get the current signOut link.
 */
export function useSignOutLink(): string {
    const contextQuery = useEntryLinkContext();
    const metaUrl = getMeta("signOutUrl", "/entry/signout");

    return makeContextualAuthLink(metaUrl, contextQuery);
}

export function makeContextualAuthLink(baseUrl: string, extraQueryParams: Record<string, any>): string {
    const url = formatUrl(baseUrl, true);

    const urlObject = new URL(url);
    const existingParams = Object.fromEntries(urlObject.searchParams.entries());
    const query = {
        ...existingParams,
        ...extraQueryParams,
    };

    if (!window.location.href.includes("/entry")) {
        query.target = window.location.href;
    }

    for (const [key, value] of Object.entries(query)) {
        urlObject.searchParams.set(key, value);
    }

    return urlObject.toString();
}
