/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { cx } from "@emotion/css";
import { checkRadioClasses } from "@library/forms/checkRadioStyles";
import { useRadioGroupContext } from "@library/forms/RadioGroupContext";
import { InformationIcon } from "@library/icons/common";
import { ToolTip } from "@library/toolTip/ToolTip";
import { t } from "@library/utility/appUtils";
import { IOptionalComponentID, useUniqueID } from "@library/utility/idUtils";
import classNames from "classnames";
import React, { ReactNode } from "react";
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
    note?: React.ReactNode;
    defaultChecked?: boolean;
    fakeFocus?: boolean;
    value: string;
    tooltip?: string;
}

/**
 * A styled, accessible checkbox component.
 */
export function RadioButton(props: IProps) {
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
                {props.tooltip && (
                    <ToolTip label={t(props.tooltip)}>
                        <span className={classes.tooltipPerOption}>{<InformationIcon />}</span>
                    </ToolTip>
                )}
            </label>
            {note && (
                <div id={noteID} className={classes.checkBoxDescription}>
                    {note}
                </div>
            )}
        </>
    );
}

// this can be used either in admin forms or in frontend forms.
// must be wrapped in a RadioGroupContext
export default function RadioButtonInRadioGroup(
    props: Omit<React.ComponentProps<typeof RadioButton>, "onChange" | "checked">,
) {
    const { onChange, value, isInline } = useRadioGroupContext();

    const controlledProps =
        onChange !== undefined
            ? {
                  onChange: () => onChange(props.value),
                  checked: value === props.value,
              }
            : {};

    return <RadioButton {...props} {...controlledProps} isHorizontal={isInline} />;
}
