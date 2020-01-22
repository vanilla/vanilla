/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useContext, useState, useEffect, useRef, useDebugValue } from "react";
import { useHistory } from "react-router";
import { useMeasure } from "@vanilla/react-utils";

interface IContextValue {
    heroExists: boolean;
    setHeroExists: (exists: boolean) => void;
    heroRect: DOMRect | null;
    setHeroRect: (rect: DOMRect | null) => void;
}

const context = React.createContext<IContextValue>({
    heroExists: false,
    setHeroExists: () => {},
    heroRect: null,
    setHeroRect: () => {},
});

export function useHeroContext() {
    const value = useContext(context);
    useDebugValue(value);
    return value;
}

/**
 * Pass this ref to hero's container to synchronize it's information with the context.
 */
export function useHeroContainerDivRef() {
    const ref = useRef<HTMLDivElement | null>(null);
    const measure = useMeasure(ref, true);
    const { setHeroRect, setHeroExists } = useHeroContext();

    useEffect(() => {
        if (ref.current) {
            // Adjust the measure for the current scroll offset.
            setHeroRect(measure);
            setHeroExists(true);
        }
    }, [ref, measure, setHeroExists, setHeroRect]);

    return ref;
}

export function HeroContextProvider(props: { children: React.ReactNode }) {
    const [heroExists, setHeroExists] = useState(false);
    const [heroRect, setHeroRect] = useState<DOMRect | null>(null);
    const history = useHistory();
    useEffect(() => {
        // Clear the hero information when navigating between pages.
        return history.listen(() => {
            setHeroExists(false);
            setHeroRect(null);
        });
    }, [history]);

    return (
        <context.Provider
            value={{
                heroExists,
                setHeroExists,
                heroRect,
                setHeroRect,
            }}
        >
            {props.children}
        </context.Provider>
    );
}
