/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useContext, useEffect, useState } from "react";
import qs from "qs";

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

    const query = {
        ...contextQuery,
        target: window.location.href,
    };

    return `/entry/signin?${qs.stringify(query)}`;
}

/**
 * Get the current register link.
 */
export function useRegisterLink(): string {
    const contextQuery = useEntryLinkContext();

    const query = {
        ...contextQuery,
        target: window.location.href,
    };

    return `/entry/register?${qs.stringify(query)}`;
}
