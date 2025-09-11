/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { createContext, useContext, useState } from "react";

import { CustomPagesAPI } from "@dashboard/appearance/customPages/CustomPagesApi";

export interface ICustomPagesContext {
    pageToDelete: CustomPagesAPI.Page["customPageID"] | null;
    setPageToDelete: (pageID: CustomPagesAPI.Page["customPageID"] | null) => void;
    pageToEdit: CustomPagesAPI.Page | "new" | null;
    setPageToEdit: (page: CustomPagesAPI.Page | "new" | null) => void;
    pageToCopy: CustomPagesAPI.Page | null;
    setPageToCopy: (page: CustomPagesAPI.Page | null) => void;
}

const defaultCustomPagesContext: ICustomPagesContext = {
    pageToDelete: null,
    setPageToDelete: () => {},
    pageToEdit: null,
    setPageToEdit: () => {},
    pageToCopy: null,
    setPageToCopy: () => {},
};

export const CustomPagesContext = createContext<ICustomPagesContext>(defaultCustomPagesContext);

export function useCustomPageContext() {
    return useContext(CustomPagesContext);
}

export function CustomPagesProvider({ children }: { children: React.ReactNode }) {
    const [pageToDelete, setPageToDelete] = useState<CustomPagesAPI.Page["customPageID"] | null>(null);
    const [pageToEdit, setPageToEdit] = useState<CustomPagesAPI.Page | "new" | null>(null);
    const [pageToCopy, setPageToCopy] = useState<CustomPagesAPI.Page | null>(null);

    return (
        <CustomPagesContext.Provider
            value={{
                pageToDelete,
                setPageToDelete,
                pageToEdit,
                setPageToEdit,
                pageToCopy,
                setPageToCopy,
            }}
        >
            {children}
        </CustomPagesContext.Provider>
    );
}
