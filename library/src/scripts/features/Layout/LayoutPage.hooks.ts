/* eslint-disable no-console */
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import apiv2 from "@library/apiv2";
import { ILayoutQuery, type IHydratedLayoutSpec } from "@library/features/Layout/LayoutRenderer.types";
import { useToast } from "@library/features/toaster/ToastContext";
import { isDevSite, isQASite, t } from "@library/utility/appUtils";
import { useQuery } from "@tanstack/react-query";
import isEqual from "lodash-es/isEqual";
import { flattenObject, logDebug, promiseTimeout } from "@vanilla/utils";
import { useEffect } from "react";

let hasLoadedLayoutBefore = false;
const initialPath = window.location.pathname;

declare global {
    interface Window {
        vanillaInitialLayout: IInitialLayout | undefined;
    }
}

// Currently hardcoded.
export function useLayoutSpec(query: ILayoutQuery) {
    const initialLayout = window.vanillaInitialLayout;
    const initialQuery = initialLayout?.query;
    const initialData = initialLayout?.data;

    const isInitialDataQueryMatch = isEqual(query, initialQuery);
    const hasDifferentPath = initialPath !== window.location.pathname;

    const toast = useToast();

    useEffect(() => {
        if (initialQuery && !hasLoadedLayoutBefore && !isInitialDataQueryMatch && !hasDifferentPath) {
            const style =
                "background-color: darkblue; color: white; font-style: italic; border: 5px solid hotpink; font-size: 2em;";
            console.log(
                "%cuseLayoutSpec: The initial query does not match the current query. This will cause a double fetch.",
                style,
            );
            console.table({ preloadedQuery: flattenObject(initialQuery), currentQuery: flattenObject(query) });

            if (isQASite()) {
                toast.addToast({
                    toastID: "useLayoutSpecMismatch",
                    body: "This page has a double fetching bug causing slower page loads and additional server load. Please report this to the developers with reproduction steps. Check the console to see the mismatching preload parameters.",
                    dismissible: true,
                });
            }

            if (isDevSite()) {
                toast.addToast({
                    toastID: "useLayoutSpecMismatch",
                    body: "This page has a double fetching bug causing slower page loads and additional server load. Fix this immediately. Check the console to see the mismatching preload parameters.",
                    dismissible: true,
                });
            }
        }
    }, [hasLoadedLayoutBefore, isInitialDataQueryMatch]);

    const layoutSpecQuery = useQuery({
        queryKey: ["layoutSpec", "lookup", query],
        queryFn: async () => {
            const layoutSpec = await apiv2.get<IHydratedLayoutSpec>("/layouts/lookup-hydrate", {
                params: query,
            });
            hasLoadedLayoutBefore = true;
            const data = layoutSpec.data;

            if (data.redirectTo) {
                // We just navigated to this url.
                // In order to avoid a double navigation, we need to replace the current history entry so that the "bad" redirecting url is no longer there.
                logDebug("Layout query returned redirect to", data.redirectTo);
                window.location.replace(data.redirectTo);
                // Give the browser some time to redirect.
                await promiseTimeout(5000);
            }

            return data;
        },
        initialData: isEqual(query, initialQuery) ? initialData : undefined,
        refetchOnMount: false,
        retry: false,
    });

    return layoutSpecQuery;
}

interface IInitialLayout {
    query: ILayoutQuery;
    data: IHydratedLayoutSpec;
}
