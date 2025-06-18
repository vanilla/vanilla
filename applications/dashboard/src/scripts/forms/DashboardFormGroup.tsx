/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { ElementType } from "react";
import { useUniqueID } from "@library/utility/idUtils";
import { DashboardFormLabel } from "@dashboard/forms/DashboardFormLabel";
import { DashboardLabelType } from "@dashboard/forms/DashboardLabelType";
import { FormGroupContext } from "./DashboardFormGroupContext";
import { cx } from "@emotion/css";
import { IFieldError } from "@vanilla/json-schema-forms";
import { dashboardFormGroupClasses } from "@dashboard/forms/DashboardFormGroup.classes";
import { useDashboardFormStyle } from "@dashboard/forms/DashboardFormStyleContext";

interface IDashboardFormGroupProps extends React.ComponentProps<typeof DashboardFormLabel> {
    tag?: ElementType;
    children?: React.ReactNode;
    inputID?: string;
    labelID?: string;
    className?: string;
    required?: boolean;
    errors?: IFieldError[];
    after?: React.ReactNode;
    noBorder?: boolean;
    isNested?: boolean;
    justified?: boolean;
}

export function DashboardFormGroup(props: IDashboardFormGroupProps) {
    const { fieldset = false } = props;

    const formStyle = useDashboardFormStyle();

    const Tag = fieldset || formStyle.groupTag === "div" ? "div" : props.tag ?? "li";
    const uniqueID = useUniqueID("formGroup-");
    const inputID = props.inputID ?? uniqueID;
    const labelID = props.labelID ?? inputID + "-label";

    const classes = dashboardFormGroupClasses();

    let labelType = props.labelType ?? DashboardLabelType.STANDARD;
    if (
        formStyle.forceVerticalLabels &&
        (
            [DashboardLabelType.STANDARD, DashboardLabelType.WIDE, DashboardLabelType.JUSTIFIED] as DashboardLabelType[]
        ).includes(labelType)
    ) {
        labelType = DashboardLabelType.VERTICAL;
    }

    if (props.inputType === "subheading") {
        return <>{props.children}</>;
    }

    return (
        <Tag
            className={cx(
                classes.formGroup,
                {
                    [`formGroup-${props.inputType}`]: !!props.inputType,
                    ["hasError"]: props.errors && !!props.errors.length,
                    [classes.vertical]: labelType === DashboardLabelType.VERTICAL,
                    [classes.noBorder]: props.noBorder,
                    [classes.isNested]: props.isNested,
                    isJustifiedGroup:
                        props.labelType === DashboardLabelType.WIDE || props.labelType === DashboardLabelType.JUSTIFIED,
                    isCompact: formStyle.compact,
                },
                "modernFormGroup",
                props.className,
            )}
            role={"group"}
            aria-labelledby={fieldset ? labelID : undefined}
        >
            <FormGroupContext.Provider
                value={{ inputID, labelID, labelType: props.labelType || DashboardLabelType.STANDARD }}
            >
                {!!props.label && <DashboardFormLabel {...props} />}
                {!props.label &&
                    !([DashboardLabelType.NONE, DashboardLabelType.VERTICAL] as DashboardLabelType[]).includes(
                        labelType,
                    ) && <DashboardFormLabel {...props} />}
                {props.children}
            </FormGroupContext.Provider>
            {props.after}
        </Tag>
    );
}
