/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState } from "react";
import { srOnly } from "@library/styles/styleHelpers";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import { useUniqueID } from "@library/utility/idUtils";
import { formToggleClasses } from "@library/forms/FormToggle.styles";
import classNames from "classnames";

interface IProps {
    enabled: boolean;
    onChange: (enabled: boolean) => void;
    className?: string;
    indeterminate?: boolean;
    id?: string;
    labelID?: string;
    accessibleLabel?: string;
    slim?: boolean;
    disabled?: boolean;
}

export function FormToggle(props: IProps) {
    const { enabled, onChange, className, indeterminate, accessibleLabel, slim, disabled, ...IDs } = props;
    const [isFocused, setIsFocused] = useState(false);

    if (IDs.labelID == null && accessibleLabel == null) {
        throw new Error("Either a labelID or accessibleLabel must be passed to <FormToggle />");
    }

    const ownLabelID = useUniqueID("formToggleLabel");
    const ownID = useUniqueID("formToggle");
    const id = IDs.id ?? ownID;
    const labelID = IDs.labelID ?? ownLabelID;
    const classes = formToggleClasses(slim ? { formToggle: { options: { slim } } } : undefined);

    return (
        <label
            onClick={e => {
                if (disabled !== undefined && !disabled) {
                    e.stopPropagation();
                    e.preventDefault();
                }
            }}
            tabIndex={0}
            className={classNames(
                props.className,
                classes.root,
                enabled && "isOn",
                indeterminate && "isIndeterminate",
                isFocused && "isFocused",
                {
                    isDisabled: disabled,
                },
            )}
        >
            <ScreenReaderContent>
                {accessibleLabel && <span id={labelID}>{accessibleLabel}</span>}
                <input
                    onFocus={() => setIsFocused(true)}
                    onBlur={() => setIsFocused(false)}
                    disabled={disabled}
                    aria-disabled={disabled}
                    type="checkbox"
                    aria-labelledby={labelID}
                    id={id}
                    checked={enabled}
                    onChange={e => {
                        onChange(e.target.checked);
                    }}
                />
            </ScreenReaderContent>
            <div className={classes.well}></div>
            <div className={classes.slider}></div>
        </label>
    );
}
