/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useContext, useState, useEffect, useRef, useDebugValue } from "react";
import { useMeasure } from "@vanilla/react-utils";
import { usePageChangeListener } from "@library/pageViews/pageViewTracking";

interface IContextValue {
    bannerExists: boolean;
    setBannerExists: (exists: boolean) => void;
    bannerRect: DOMRect | null;
    setBannerRect: (rect: DOMRect | null) => void;
    overlayTitleBar: boolean;
    setOverlayTitleBar: (exists: boolean) => void;
    renderedH1: boolean;
    setRenderedH1: (exists: boolean) => void;
}

const context = React.createContext<IContextValue>({
    bannerExists: false,
    setBannerExists: () => {},
    bannerRect: null,
    setBannerRect: () => {},
    overlayTitleBar: false,
    setOverlayTitleBar: () => {},
    renderedH1: false,
    setRenderedH1: () => {},
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
    const [overlayTitleBar, setOverlayTitleBar] = useState(false);
    const [bannerRect, setBannerRect] = useState<DOMRect | null>(null);
    const [renderedH1, setRenderedH1] = useState(false);
    usePageChangeListener(() => {
        setBannerExists(false);
        setBannerRect(null);
        setOverlayTitleBar(false);
    });
    return (
        <context.Provider
            value={{
                bannerExists,
                setBannerExists,
                bannerRect,
                setBannerRect,
                overlayTitleBar,
                setOverlayTitleBar,
                renderedH1,
                setRenderedH1,
            }}
        >
            {props.children}
        </context.Provider>
    );
}

export function BannerContextProviderNoHistory(props: { children: React.ReactNode }) {
    const [bannerExists, setBannerExists] = useState(false);
    const [bannerRect, setBannerRect] = useState<DOMRect | null>(null);
    const [overlayTitleBar, setOverlayTitleBar] = useState(false);
    const [renderedH1, setRenderedH1] = useState(false);
    return (
        <context.Provider
            value={{
                bannerExists,
                setBannerExists,
                bannerRect,
                setBannerRect,
                overlayTitleBar,
                setOverlayTitleBar,
                renderedH1,
                setRenderedH1,
            }}
        >
            {props.children}
        </context.Provider>
    );
}
