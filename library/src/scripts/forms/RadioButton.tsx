/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { ReactNode } from "react";
import { t } from "@library/utility/appUtils";
import { IOptionalComponentID, useUniqueID } from "@library/utility/idUtils";
import { checkRadioClasses } from "@library/forms/checkRadioStyles";
import classNames from "classnames";
import { cx } from "@emotion/css";

interface IProps extends IOptionalComponentID {
    id?: string;
    className?: string;
    checked?: boolean;
    disabled?: boolean;
    onChange?: any;
    onChecked?: () => void;
    label: string | ReactNode;
    name?: string;
    isHorizontal?: boolean;
    note?: string;
    defaultChecked?: boolean;
    fakeFocus?: boolean;
    value?: string;
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
    const { isHorizontal, note } = props;

    const noteID = useUniqueID("radioButtonNote");

    return (
        <>
            <label className={cx(classes.root, { isHorizontal }, props.className)}>
                <input
                    className={cx(classes.input, "exclude-icheck", { "focus-visible": props.fakeFocus })}
                    onChange={(e) => {
                        props.onChange?.(e);
                        if (e.target.checked) {
                            props.onChecked?.();
                        }
                    }}
                    aria-disabled={props.disabled}
                    name={props.name}
                    disabled={props.disabled}
                    type="radio"
                    checked={props.checked}
                    defaultChecked={props.defaultChecked}
                    tabIndex={0}
                    aria-describedby={note ? noteID : undefined}
                    value={props.value}
                />
                <span aria-hidden={true} className={classNames(classes.iconContainer, classes.disk)}>
                    <svg className={classes.diskIcon}>
                        <title>{t("Radio Button")}</title>
                        <circle fill="currentColor" cx="3" cy="3" r="3" />
                    </svg>
                </span>
                {props.label && (
                    <span id={labelID} className={classes.label}>
                        {props.label}
                    </span>
                )}
            </label>
            {note && (
                <div id={noteID} className={"info"}>
                    {note}
                </div>
            )}
        </>
    );
}
