/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useCallback, useEffect, useMemo, useRef } from "react";
import qs from "qs";
import { useHistory, useLocation } from "react-router";
import debounce from "lodash/debounce";
import History from "history";
import { useLastValue } from "@vanilla/react-utils";
import { Uri } from "monaco-editor";

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

export function useQueryStringSync(value: IStringMap, defaults?: IStringMap, syncOnFirstMount?: boolean) {
    const history = useHistory();
    const location = useLocation();
    const isMountedRef = useRef(false);

    const debouncedQsUpdate = useCallback(
        debounce((location: History.LocationDescriptor) => {
            if (!isMountedRef.current) {
                // Do not perform any updates after we've unmounted.
                // This can result in bugs like https://github.com/vanilla/support/issues/4202
                return;
            }
            history.replace(location);
        }),
        [history],
    );

    const filteredCurrent = qs.stringify(getFilteredValue(value, defaults || {}));
    const filteredLast = useLastValue(filteredCurrent);

    // Handle continuous updates updates.
    useEffect(() => {
        // Only runs when mounted.
        if (isMountedRef.current && filteredLast !== filteredCurrent) {
            debouncedQsUpdate({
                ...location,
                search: filteredCurrent,
            });
        }
    }, [filteredLast, filteredCurrent, debouncedQsUpdate, location]);

    useEffect(() => {
        // Handles first mount if needed.
        isMountedRef.current = true;
        if (syncOnFirstMount) {
            debouncedQsUpdate({
                ...location,
                search: filteredCurrent,
            });
        }

        return () => {
            isMountedRef.current = false;
        };
    }, []);
}

/**
 * Get a version of the query string object with only keys that have values.
 */
function getFilteredValue(inputValue: IStringMap, defaults: IStringMap): IStringMap | null {
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

    return filteredValue;
}
