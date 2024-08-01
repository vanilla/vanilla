/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IDeveloperProfileSpan } from "@dashboard/developer/profileViewer/DeveloperProfile.types";
import { createContext, useContext, useState } from "react";

interface IContext {
    selectedSpan: IDeveloperProfileSpan | null;
    setSelectedSpan: (span: IDeveloperProfileSpan | null) => void;
    filteredSpanTypes: string[] | null;
    setFilteredSpanTypes: (types: string[] | null) => void;
}

const context = createContext<IContext>({
    selectedSpan: null,
    setSelectedSpan: () => {},
    filteredSpanTypes: null,
    setFilteredSpanTypes: () => {},
});

export function useDeveloperProfile() {
    return useContext(context);
}

export function DeveloperProfileProvider(props: { children: React.ReactNode }) {
    const [selectedSpan, setSelectedSpan] = useState<IDeveloperProfileSpan | null>(null);
    const [filteredSpanTypes, setFilteredSpanTypes] = useState<string[] | null>(null);

    return (
        <context.Provider value={{ selectedSpan, setSelectedSpan, filteredSpanTypes, setFilteredSpanTypes }}>
            {props.children}
        </context.Provider>
    );
}
