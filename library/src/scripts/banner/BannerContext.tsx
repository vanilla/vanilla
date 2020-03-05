/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useContext, useState, useEffect, useRef, useDebugValue } from "react";
import { useHistory } from "react-router";
import { useMeasure } from "@vanilla/react-utils";

interface IContextValue {
    bannerExists: boolean;
    setBannerExists: (exists: boolean) => void;
    bannerRect: DOMRect | null;
    setBannerRect: (rect: DOMRect | null) => void;
}

const context = React.createContext<IContextValue>({
    bannerExists: false,
    setBannerExists: () => {},
    bannerRect: null,
    setBannerRect: () => {},
});

export function useBannerContext() {
    const value = useContext(context);
    useDebugValue(value);
    return value;
}

/**
 * Pass this ref to banner's container to synchronize it's information with the context.
 */
export function useBannerContainerDivRef() {
    const ref = useRef<HTMLDivElement | null>(null);
    const measure = useMeasure(ref, true);
    const { setBannerRect, setBannerExists } = useBannerContext();

    useEffect(() => {
        if (ref.current) {
            // Adjust the measure for the current scroll offset.
            setBannerRect(measure);
            setBannerExists(true);
        }
    }, [ref, measure, setBannerExists, setBannerRect]);

    return ref;
}

export function BannerContextProvider(props: { children: React.ReactNode }) {
    const [bannerExists, setBannerExists] = useState(false);
    const [bannerRect, setBannerRect] = useState<DOMRect | null>(null);
    const history = useHistory();
    useEffect(() => {
        // Clear the banner information when navigating between pages.
        return history.listen(() => {
            setBannerExists(false);
            setBannerRect(null);
        });
    }, [history]);

    return (
        <context.Provider
            value={{
                bannerExists,
                setBannerExists,
                bannerRect,
                setBannerRect,
            }}
        >
            {props.children}
        </context.Provider>
    );
}
