/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { t } from "@library/utility/appUtils";
import { IOptionalComponentID, useUniqueID } from "@library/utility/idUtils";
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
    isHorizontal?: boolean;
    note?: string;
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
            <label className={classNames(classes.root, props.className, { isHorizontal })}>
                <input
                    className={classNames(classes.input, "exclude-icheck")}
                    onChange={props.onChange}
                    aria-disabled={props.disabled}
                    name={props.name}
                    disabled={props.disabled}
                    type="radio"
                    checked={props.checked}
                    tabIndex={0}
                    aria-describedBy={note ? noteID : undefined}
                />
                <span aria-hidden={true} className={classNames(classes.iconContainer, classes.disk)}>
                    <span className={classes.state}>
                        <svg className={classes.diskIcon}>
                            <title>{t("Radio Button")}</title>
                            <circle fill="currentColor" cx="3" cy="3" r="3" />
                        </svg>
                    </span>
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
