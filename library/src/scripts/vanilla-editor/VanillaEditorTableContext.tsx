/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React, { createContext, useContext, useState } from "react";
import { MyTableState } from "@library/vanilla-editor/typescript";

interface IVanillaEditorTableContext {
    tablesByID: Record<string, MyTableState>;
    updateTableState: (tableID: string, newValues: Partial<MyTableState>) => void;
}

const VanillaEditorTableContext = createContext<IVanillaEditorTableContext>({
    tablesByID: {},
    updateTableState: () => {},
});

export function VanillaEditorTableProvider(props: { children: React.ReactNode }) {
    const [tablesByID, setTablesByID] = useState<Record<string, MyTableState>>({});

    const updateTableState = (tableID: string, newValues: Partial<MyTableState>) => {
        if (!tableID) {
            return;
        }
        setTablesByID((prev) => ({
            ...prev,
            [tableID]: {
                ...prev[tableID],
                ...newValues,
            },
        }));
    };

    return (
        <VanillaEditorTableContext.Provider
            value={{
                tablesByID,
                updateTableState,
            }}
        >
            {props.children}
        </VanillaEditorTableContext.Provider>
    );
}

export function useVanillaEditorTable() {
    return useContext(VanillaEditorTableContext);
}
