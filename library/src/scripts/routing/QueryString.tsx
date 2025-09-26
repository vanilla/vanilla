/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useCallback, useEffect, useMemo, useRef } from "react";
import * as qs from "qs-esm";
import { useHistory, useLocation } from "react-router";
import debounce from "lodash-es/debounce";
import { useLastValue } from "@vanilla/react-utils";

interface IStringMap {
    [key: string]: any;
}

interface IProps {
    value: IStringMap;
    defaults?: IStringMap;
    syncOnFirstMount?: boolean;
}

export default function QueryString(props: IProps) {
    useQueryStringSync(props.value, props.defaults, props.syncOnFirstMount);

    return <React.Fragment />;
}

export function useUrlSearchParams(): URLSearchParams {
    const location = useLocation();
    const params = useMemo(() => {
        return new URLSearchParams(location.search);
    }, [location]);
    return params;
}

export function useQueryStringSync(
    value: IStringMap,
    defaults?: IStringMap,
    syncOnFirstMount?: boolean,
    keepHash?: boolean,
) {
    const history = useHistory();
    const location = useLocation();
    const isMountedRef = useRef(false);

    const debouncedQsUpdate = useCallback(
        debounce((query: string) => {
            if (!isMountedRef.current) {
                // Do not perform any updates after we've unmounted.
                // This can result in bugs like https://github.com/vanilla/support/issues/4202
                return;
            }
            let newPath = `${window.location.pathname}`;
            if (query.length > 0) {
                newPath += "?" + decodeURIComponent(query);
            }

            if (keepHash && !!location.hash) {
                newPath += location.hash;
            }
            window.history.replaceState(null, "", newPath);
        }),
        [history],
    );

    const queryFromUrl = qs.parse(location.search, { ignoreQueryPrefix: true });

    const filteredCurrent = qs.stringify(getFilteredValue(value, defaults || {}, queryFromUrl));
    const filteredLast = useLastValue(filteredCurrent);

    // Handle continuous updates updates.
    useEffect(() => {
        // Only runs when mounted.
        if (isMountedRef.current && filteredLast !== filteredCurrent) {
            debouncedQsUpdate(filteredCurrent);
        }
    }, [filteredLast, filteredCurrent, debouncedQsUpdate, location]);

    useEffect(() => {
        // Handles first mount if needed.
        isMountedRef.current = true;
        if (syncOnFirstMount) {
            debouncedQsUpdate(filteredCurrent);
        }

        return () => {
            isMountedRef.current = false;
        };
    }, []);
}

/**
 * Get a version of the query string object with only keys that have values.
 * If there were params in the URL that are not in the value or defaults, they will be preserved.
 */
export function getFilteredValue(
    inputValue: IStringMap,
    defaults: IStringMap,
    paramsFromUrl: IStringMap,
): IStringMap | null {
    let filteredValue: IStringMap | null = null;

    for (const [key, value] of Object.entries(inputValue)) {
        if (value === null || value === undefined || value === "") {
            continue;
        }

        if (key !== "scope" && defaults[key] === value) {
            continue;
        }

        if (filteredValue === null) {
            filteredValue = {};
        }

        filteredValue[key] = value;
    }

    // we might have more query params in url that are not in the value or defaults, we should preserve those
    const queryParamsNotMatchingValueOrDefaults = Object.fromEntries(
        Object.entries(paramsFromUrl).filter(([key]) => !(key in inputValue) && !(key in defaults)),
    );
    if (Object.keys(queryParamsNotMatchingValueOrDefaults).length > 0) {
        filteredValue = { ...filteredValue, ...queryParamsNotMatchingValueOrDefaults };
    }

    return filteredValue;
}
