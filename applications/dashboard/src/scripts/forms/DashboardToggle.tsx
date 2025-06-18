/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useContext } from "react";
import { FormGroupContext, useFormGroup, useOptionalFormGroup } from "@dashboard/forms/DashboardFormGroupContext";
import { DashboardLabelType } from "@dashboard/forms/DashboardLabelType";
import classNames from "classnames";
import { visibility } from "@library/styles/styleHelpers";
import { IFieldError } from "@library/@types/api/core";
import ErrorMessages from "@library/forms/ErrorMessages";
import { dashboardClasses } from "@dashboard/forms/dashboardStyles";
import { cx } from "@emotion/css";
import { ToolTip } from "@library/toolTip/ToolTip";
import { DashboardInputWrap } from "@dashboard/forms/DashboardInputWrap";
import { FormToggle } from "@library/forms/FormToggle";
import { useDashboardFormStyle } from "@dashboard/forms/DashboardFormStyleContext";

type ICommonProps = {
    errors?: IFieldError[];
    wrapperClassName?: string;
};

type ILegacyProps = {
    checked: boolean;
    onChange: (newValue: boolean) => void;
    inProgress?: boolean;
    disabled?: boolean;
    tooltip?: string;
    name?: string;
} & ICommonProps;
type IModernProps = Omit<React.ComponentProps<typeof FormToggle>, "visibleLabel" | "visibleLabelUrl"> & ICommonProps;

type IProps = ILegacyProps | IModernProps;

function ensureModernProps(props: IProps): IModernProps {
    if ("checked" in props) {
        const { checked, onChange, inProgress, disabled, tooltip } = props as ILegacyProps;
        return {
            ...props,
            enabled: checked,
            onChange,
            indeterminate: inProgress,
            disabled,
            tooltip,
            errors: props.errors,
        };
    }

    return props as IModernProps;
}

export function DashboardToggle(props: IProps) {
    const formGroup = useOptionalFormGroup();
    props = ensureModernProps(props);

    const classes = dashboardClasses();

    const { inputID, labelID } = formGroup || {};

    const formStyle = useDashboardFormStyle();

    let toggle = (
        <FormToggle
            slim={formStyle.compact}
            indeterminate={props.indeterminate}
            enabled={props.enabled}
            onChange={props.onChange}
            disabled={props.disabled}
            accessibleLabel={props.accessibleLabel}
            labelID={labelID || props.labelID}
            id={inputID || props.id}
            tooltip={props.tooltip}
            name={props.name}
        />
    );

    return (
        <DashboardInputWrap className={cx(props.disabled ? classes.disabled : undefined, props.wrapperClassName)}>
            {toggle}
            {props.errors && <ErrorMessages errors={props.errors} />}
        </DashboardInputWrap>
    );
}
