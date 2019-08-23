/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useContext, useMemo } from "react";
import { useUniqueID } from "@library/utility/idUtils";
import { DashboardFormLabel, FormLabelType } from "@dashboard/forms/DashboardFormLabel";
import { useThrowError } from "@vanilla/react-utils";

interface IProps {
    label?: string;
    description?: string;
    labelType?: FormLabelType;
    tag?: keyof JSX.IntrinsicElements;
    children: React.ReactNode;
}

interface IGroupContext {
    inputID: string;
    hasLabel: boolean;
}

const FormGroupContext = React.createContext<IGroupContext | null>(null);

export function useFormGroup(): IGroupContext {
    const context = useContext(FormGroupContext);

    const throwError = useThrowError();
    if (context === null) {
        throwError(
            new Error(
                "Attempting to create a <DashboardFormLabel /> without a form group. Be sure to place it in a <DashboardFormGroup />",
            ),
        );
    }
    return context!;
}

export function DashboardFormGroup(props: IProps) {
    const LiTag = (props.tag || "li") as "li";
    const inputID = useUniqueID("formGroup-");
    const hasLabel = !!(props.label || props.children);

    return (
        <LiTag className="form-group">
            <FormGroupContext.Provider value={{ inputID, hasLabel }}>
                <DashboardFormLabel {...props} />
                {props.children}
            </FormGroupContext.Provider>
        </LiTag>
    );
}
