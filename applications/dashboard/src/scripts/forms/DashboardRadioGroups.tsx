/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useContext } from "react";
import { useFormGroup } from "@dashboard/forms/DashboardFormGroup";
import classNames from "classnames";
import { useThrowError } from "@vanilla/react-utils";
import { DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";

interface IRadioGroupContext {
    isInline?: boolean;
    onChange?: (value: string) => void;
    value?: string;
}

const RadioGroupContext = React.createContext<IRadioGroupContext | null>(null);

export function useDashboardRadioGroup() {
    const context = useContext(RadioGroupContext);

    const throwError = useThrowError();
    if (context === null) {
        throwError(
            new Error(
                "Attempting to use a radio group without specifying a group. Be sure to wrap it in a <DashboardRadioGroup />",
            ),
        );
    }
    return context!;
}

interface IProps extends IRadioGroupContext {
    children: React.ReactNode;
    type?: "group" | "radioogroup";
}

export function DashboardRadioGroup(props: IProps) {
    const { labelID, labelType } = useFormGroup();
    const rootClass = labelType === DashboardLabelType.WIDE ? "input-wrap-right" : "input-wrap";
    const type = props.type || "radioogroup";

    return (
        <RadioGroupContext.Provider value={props}>
            <div
                className={classNames(rootClass, { inline: props.isInline })}
                role={props.type}
                aria-labelledby={labelID}
            >
                {props.children}
            </div>
        </RadioGroupContext.Provider>
    );
}

export function DashboardCheckGroup(props: IProps) {
    return <DashboardRadioGroup {...props} type="group" />;
}
