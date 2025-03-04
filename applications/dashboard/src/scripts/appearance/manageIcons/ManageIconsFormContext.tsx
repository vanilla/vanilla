/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IManageIconsForm } from "@dashboard/appearance/manageIcons/ManageIconsForm";
import { createContext, useContext } from "react";

const ManageIconsFormContext = createContext<IManageIconsForm>({} as any);

export function useManageIconsForm() {
    return useContext(ManageIconsFormContext);
}

export function ManageIconsFormContextProvider(
    props: {
        children: React.ReactNode;
    } & IManageIconsForm,
) {
    const { children, ..._props } = props;

    return (
        <div>
            <ManageIconsFormContext.Provider
                value={{
                    ..._props,
                }}
            >
                {props.children}
            </ManageIconsFormContext.Provider>
        </div>
    );
}
