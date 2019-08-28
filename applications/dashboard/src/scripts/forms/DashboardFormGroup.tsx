/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useContext, useMemo } from "react";
import { useUniqueID } from "@library/utility/idUtils";
import { DashboardFormLabel, DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";
import { useThrowError } from "@vanilla/react-utils";
import classNames from "classnames";

interface IProps {
    label: string;
    description?: React.ReactNode;
    labelType?: DashboardLabelType;
    tag?: keyof JSX.IntrinsicElements;
    children: React.ReactNode;
    isIndependant?: boolean; // Setting this resets the side margins.
}

interface IGroupContext {
    inputID: string;
    labelID: string;
    labelType: DashboardLabelType;
}

const FormGroupContext = React.createContext<IGroupContext | null>(null);

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

export function DashboardFormGroup(props: IProps) {
    const LiTag = (props.tag || "li") as "li";
    const inputID = useUniqueID("formGroup-");
    const labelID = inputID + "-label";

    return (
        <LiTag className={classNames("form-group", props.isIndependant && "row")}>
            <FormGroupContext.Provider
                value={{ inputID, labelID, labelType: props.labelType || DashboardLabelType.STANDARD }}
            >
                <DashboardFormLabel {...props} />
                {props.children}
            </FormGroupContext.Provider>
        </LiTag>
    );
}
