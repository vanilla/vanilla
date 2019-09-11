/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { useFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";
import classNames from "classnames";
import { visibility, srOnly } from "@library/styles/styleHelpers";

interface IProps {
    checked: boolean;
    onChange: (newValue: boolean) => void;
    inProgress?: boolean;
    disabled?: boolean;
}

export function DashboardToggle(props: IProps) {
    const { inputID, labelType } = useFormGroup();
    const rootClass = labelType === DashboardLabelType.WIDE ? "input-wrap-right" : "input-wrap";

    return (
        <div className={rootClass}>
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
                        onChange={event => props.onChange(!!event.target.checked)}
                    />
                    <div className="toggle-well" />
                    <div className="toggle-slider" />
                </div>
            </label>
        </div>
    );
}
