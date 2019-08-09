/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { t } from "@library/utility/appUtils";
import { getRequiredID, IOptionalComponentID, useUniqueID } from "@library/utility/idUtils";
import { checkRadioClasses } from "@library/forms/checkRadioStyles";
import classNames from "classnames";

interface IProps extends IOptionalComponentID {
    id?: string;
    className?: string;
    checked?: boolean;
    disabled?: boolean;
    onChange?: any;
    label: string;
    name?: string;
}

interface IState {
    id: string;
}

/**
 * A styled, accessible checkbox component.
 */
export default function RadioButton(props: IProps) {
    const labelID = useUniqueID("radioButton-label");
    const classes = checkRadioClasses();

    return (
        <label className={classNames("radioButton", classes.root)}>
            <input
                className={classNames("radioButton-input", classes.input)}
                onChange={props.onChange}
                aria-disabled={props.disabled}
                name={props.name}
                disabled={props.disabled}
                type="radio"
                checked={props.checked}
                tabIndex={0}
            />
            <span className={classNames("radioButton-disk", classes.iconContainer, classes.disk)}>
                <span className={classNames("checkbox-state", classes.state)}>
                    <svg className={classNames(classes.diskIcon, "radioButton-icon", "radioButton-diskIcon")}>
                        <title>{t("Radio Button")}</title>
                        <circle fill="currentColor" cx="3" cy="3" r="3" />
                    </svg>
                </span>
            </span>
            {props.label && (
                <span id={labelID} className={classNames("radioButton-label", classes.label)}>
                    {props.label}
                </span>
            )}
        </label>
    );
}
