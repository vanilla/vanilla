/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useContext, useState, useEffect, useRef, useDebugValue } from "react";
import { useHistory } from "react-router";
import { useMeasure } from "@vanilla/react-utils";

interface IContextValue {
    splashExists: boolean;
    setSplashExists: (exists: boolean) => void;
    splashRect: DOMRect | null;
    setSplashRect: (rect: DOMRect | null) => void;
}

const context = React.createContext<IContextValue>({
    splashExists: false,
    setSplashExists: () => {},
    splashRect: null,
    setSplashRect: () => {},
});

export function useSplashContext() {
    const value = useContext(context);
    useDebugValue(value);
    return value;
}

/**
 * Pass this ref to splash's container to synchronize it's information with the context.
 */
export function useSplashContainerDivRef() {
    const ref = useRef<HTMLDivElement | null>(null);
    const measure = useMeasure(ref);
    const { setSplashRect, setSplashExists } = useSplashContext();

    useEffect(() => {
        if (ref.current) {
            setSplashRect(measure);
            setSplashExists(true);
        }
    }, [ref, measure, setSplashExists, setSplashRect]);

    return ref;
}

export function SplashContextProvider(props: { children: React.ReactNode }) {
    const [splashExists, setSplashExists] = useState(false);
    const [splashRect, setSplashRect] = useState<DOMRect | null>(null);
    const history = useHistory();
    useEffect(() => {
        // Clear the splash information when navigating between pages.
        return history.listen(() => {
            setSplashExists(false);
            setSplashRect(null);
        });
    }, [history]);

    return (
        <context.Provider
            value={{
                splashExists,
                setSplashExists,
                splashRect,
                setSplashRect,
            }}
        >
            {props.children}
        </context.Provider>
    );
}
