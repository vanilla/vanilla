/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { logWarning } from "@vanilla/utils";
import React, { useState, useContext } from "react";

type RecordToggle = (recordType: string, recordID: number) => void;

interface ISiteNavCtx {
    categoryRecordType: string;
    toggleItem: RecordToggle;
    openItem: RecordToggle;
    closeItem: RecordToggle;
    openRecords: {
        [recordType: string]: Set<number>;
    };
}

interface IProps {
    children: React.ReactNode;
    categoryRecordType: string;
}

const noop = () => {
    logWarning("It looks like you forgot to initialize your SiteNavContext. Be sure to use `<SiteNavProvider />`");
};

interface IOpenRecords {
    [recordType: string]: Set<number>;
}

const defaultContext: ISiteNavCtx = {
    categoryRecordType: "item",
    toggleItem: noop,
    openItem: noop,
    closeItem: noop,
    openRecords: {},
};

export const SiteNavContext = React.createContext<ISiteNavCtx>(defaultContext);

export function useSiteNavContext() {
    return useContext(SiteNavContext);
}

/**
 * Context provider that tracks the open/closed states of site nav menus.
 * This wraps `SiteNavContext.Provider` with some nice defaults.
 *
 * This helps to keep nav toggles consistent across page navigations.
 */
export default function SiteNavProvider(props: IProps) {
    const [openRecords, setOpenRecords] = useState<IOpenRecords>({});

    /**
     * Open an item in the nav.
     */
    const openItem = (recordType: string, recordID: number) => {
        const records = openRecords[recordType] || new Set();
        records.add(recordID);
        setOpenRecords({
            ...openRecords,
            [recordType]: records,
        });
    };

    /**
     * Close an item in the nav.
     */
    const closeItem = (recordType: string, recordID: number) => {
        const records = openRecords[recordType];
        if (!records) {
            return;
        }
        if (records.has(recordID)) {
            records.delete(recordID);
        }
        setOpenRecords({
            ...openRecords,
            [recordType]: records,
        });
    };

    /**
     * Toggle an item in the nav.
     */
    const toggleItem = (recordType: string, recordID: number) => {
        const records = openRecords[recordType];
        if (records && records.has(recordID)) {
            closeItem(recordType, recordID);
        } else {
            openItem(recordType, recordID);
        }
    };

    return (
        <SiteNavContext.Provider
            value={{
                categoryRecordType: props.categoryRecordType,
                openItem: openItem,
                closeItem: closeItem,
                toggleItem: toggleItem,
                openRecords: openRecords,
            }}
        >
            {props.children}
        </SiteNavContext.Provider>
    );
}
