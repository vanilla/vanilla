/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useContext } from "react";
import { FormGroupContext, useFormGroup } from "@dashboard/forms/DashboardFormGroupContext";
import { DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";
import classNames from "classnames";
import { visibility } from "@library/styles/styleHelpers";
import { IFieldError } from "@library/@types/api/core";
import ErrorMessages from "@library/forms/ErrorMessages";
import { dashboardClasses } from "@dashboard/forms/dashboardStyles";
import { cx } from "@emotion/css";

interface IProps {
    checked: boolean;
    onChange: (newValue: boolean) => void;
    inProgress?: boolean;
    disabled?: boolean;
    errors?: IFieldError[];
}

export function DashboardToggle(props: IProps) {
    const formGroup = useContext(FormGroupContext);

    const classes = dashboardClasses();

    const { inputID, labelType } = formGroup || {};
    const rootClass = labelType === DashboardLabelType.WIDE ? "input-wrap-right" : "input-wrap";

    return (
        <div className={cx(rootClass, props.disabled && classes.disabled)}>
            <label
                className={classNames("toggle-wrap", {
                    "toggle-wrap-on": props.checked,
                    "toggle-wrap-off": !props.checked,
                })}
            >
                <div
                    className={classNames({
                        "toggle-wrap-active": props.inProgress,
                    })}
                >
                    <input
                        disabled={props.disabled || props.inProgress}
                        id={inputID}
                        type="checkbox"
                        className={classNames(visibility().visuallyHidden, "toggle-input")}
                        checked={props.checked}
                        onChange={(event) => props.onChange(!!event.target.checked)}
                    />
                    <div className="toggle-well" />
                    <div className="toggle-slider" />
                </div>
            </label>
            {props.errors && <ErrorMessages errors={props.errors} />}
        </div>
    );
}
