/**
 * @author Daisy Barrette <dbarrette@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { createContext, ReactNode, useContext, useState } from "react";

interface IParentRecordContext {
    parentRecordType?: string;
    parentRecordID?: string;
}

export const ParentRecordContext = createContext<IParentRecordContext>({
    parentRecordType: "none",
});

export function useParentRecordContext() {
    return useContext(ParentRecordContext);
}

export function ParentRecordContextProvider(props: any) {
    return (
        <ParentRecordContext.Provider
            value={{ parentRecordType: props?.parentRecordType, parentRecordID: props?.parentRecordID }}
        >
            {props.children}
        </ParentRecordContext.Provider>
    );
}
