/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IBoxOptions, IPartialBoxOptions } from "@library/styles/cssUtilsTypes";
import React, { useContext, useLayoutEffect, useState } from "react";

///
/// Options Provider.
///
interface IPageBoxContext {
    options?: IPartialBoxOptions;
}

const context = React.createContext<IPageBoxContext>({});

export function usePageBoxContext() {
    return useContext(context);
}

export function PageBoxContextProvider(props: React.PropsWithChildren<IPageBoxContext>) {
    const { children, options } = props;
    return <context.Provider value={{ options }}>{children}</context.Provider>;
}

///
/// Depth Provider.
///

interface IPageBoxDepth {
    depth?: number;
    boxRef?: React.RefObject<HTMLElement>;
    isRealBox?: boolean;
}

const depthContext = React.createContext<IPageBoxDepth>({
    isRealBox: false,
});

export function usePageBoxDepthContext() {
    return useContext(depthContext);
}

export function useCalculatedDepth(currentBoxRef?: React.RefObject<HTMLElement>) {
    const parentContext = usePageBoxDepthContext();
    const [calcedDepth, setCalcedDepth] = useState<number>(parentContext.depth != null ? parentContext.depth + 1 : 2);

    useLayoutEffect(() => {
        if (parentContext.boxRef) {
            return;
        }
        const closestPanel = currentBoxRef?.current?.closest(".Panel");

        if (closestPanel) {
            setCalcedDepth(4);
        }
    }, [currentBoxRef, parentContext.boxRef, setCalcedDepth]);

    return calcedDepth;
}

export function PageBoxDepthContextProvider(props: React.PropsWithChildren<IPageBoxDepth>) {
    const { children, depth, boxRef } = props;
    const calcedDepth = useCalculatedDepth(boxRef);
    return (
        <depthContext.Provider value={{ boxRef, depth: depth ?? calcedDepth, isRealBox: true }}>
            {children}
        </depthContext.Provider>
    );
}
