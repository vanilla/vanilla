/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useReducer, useEffect, useContext, useDebugValue, useState, useCallback, useRef } from "react";
import { useHistory } from "react-router";
import * as H from "history";
import { siteUrl } from "@library/utility/appUtils";
import { useLinkContext, makeLocationDescriptorObject } from "@library/routing/links/LinkContextProvider";

const DEFAULT_FALLBACK = siteUrl("/");

const context = React.createContext<{
    historyDepth: number;
    canGoBack: boolean;
    backFallbackUrl: string;
    setBackFallbackUrl: (newUrl: string) => void;
    navigateBack: (customFallback?: string) => void;
}>({
    historyDepth: 1,
    canGoBack: false,
    backFallbackUrl: DEFAULT_FALLBACK,
    setBackFallbackUrl: () => {},
    navigateBack: () => (window.location.href = DEFAULT_FALLBACK),
});

/**
 * Provider for measuring how deep in our dynamic routing history we are.
 */
export function BackRoutingProvider(props: { children: React.ReactNode }) {
    const [backFallbackUrl, setBackFallbackUrl] = useState(DEFAULT_FALLBACK);
    const ignoreRef = useRef<boolean>(false);

    const [{ historyDepth }, dispatch] = useReducer(
        (
            nextState: { historyDepth: number },
            action: {
                type: H.Action;
                location: H.Location;
            },
        ) => {
            if (ignoreRef.current === true) {
                ignoreRef.current = false;
                // Ignored so our "fake" back routing doesn't create an entry.
                return nextState;
            }

            if (action.type === "PUSH") {
                return { historyDepth: nextState.historyDepth + 1 };
            } else if (action.type === "POP") {
                return { historyDepth: nextState.historyDepth - 1 };
            }
            return nextState;
        },
        { historyDepth: 1 },
    );

    const history = useHistory();
    const { pushSmartLocation } = useLinkContext();
    const canGoBack = historyDepth > 1;

    const navigateBack = useCallback(
        (url?: string) => {
            if (canGoBack) {
                history.goBack();
            } else {
                ignoreRef.current = true;
                pushSmartLocation(url ?? backFallbackUrl);
            }
        },
        [canGoBack, history, pushSmartLocation, backFallbackUrl],
    );

    useEffect(() => {
        const unregister = history.listen((location: H.Location, action: H.Action) => {
            dispatch({ type: action, location });
        });

        return unregister;
    }, [dispatch, history]);

    const value = {
        canGoBack,
        historyDepth,
        backFallbackUrl,
        setBackFallbackUrl,
        navigateBack,
    };

    return <context.Provider value={value}>{props.children}</context.Provider>;
}

/**
 * Hook for knowing how deep in our routing history that we are.
 */
export function useBackRouting() {
    const value = useContext(context);
    useDebugValue(value);
    return value;
}

/**
 * Component version of `useFallbackBackUrl`.
 */
export function FallbackUrlSetter(props: { url: string }) {
    useFallbackBackUrl(props.url);
    return <React.Fragment />;
}

/**
 * Hook to set some url as the fallback backlink as long as it's active.
 */
export function useFallbackBackUrl(url?: string) {
    const { setBackFallbackUrl } = useBackRouting();

    useEffect(() => {
        // Sync this value into the context as long as it's being rendered.
        if (url) {
            setBackFallbackUrl(url);
        }

        return () => {
            // Cleanup to the default when we unmount.
            setBackFallbackUrl(DEFAULT_FALLBACK);
        };
    }, [setBackFallbackUrl, url]);
}
