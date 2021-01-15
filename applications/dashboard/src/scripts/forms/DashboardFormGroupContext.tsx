import React, { useContext } from "react";
import { DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";
import { useThrowError } from "@vanilla/react-utils";

interface IGroupContext {
    inputID: string;
    labelID: string;
    labelType: DashboardLabelType;
}
export const FormGroupContext = React.createContext<IGroupContext | null>(null);

export function useFormGroup(): IGroupContext {
    const context = useContext(FormGroupContext);

    const throwError = useThrowError();
    if (context === null) {
        throwError(
            new Error(
                "Attempting to create a dashboard from component without a form group. Be sure to place it in a <DashboardFormGroup />",
            ),
        );
    }
    return context!;
}
