/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IBoxOptions, IPartialBoxOptions } from "@library/styles/cssUtilsTypes";
import React, { useContext } from "react";

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
