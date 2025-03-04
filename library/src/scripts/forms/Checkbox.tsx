/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { visibility } from "@library/styles/styleHelpers";
import { ToolTip, ToolTipIcon } from "@library/toolTip/ToolTip";
import { t } from "@library/utility/appUtils";
import { IOptionalComponentID, useUniqueID } from "@library/utility/idUtils";
import classNames from "classnames";
import React from "react";
import { checkRadioClasses } from "./checkRadioStyles";
import { InformationIcon } from "@library/icons/common";
import { Icon, IconType } from "@vanilla/icons";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { css, cx } from "@emotion/css";

interface IProps extends IOptionalComponentID {
    id?: string;
    className?: string;
    checked?: boolean;
    disabled?: boolean;
    onChange?: (event: React.ChangeEvent<HTMLInputElement>) => void;
    onBlur?: React.InputHTMLAttributes<HTMLInputElement>["onBlur"];
    label?: React.ReactNode;
    "aria-labelledby"?: string;
    "aria-describedby"?: string;
    labelBold?: boolean;
    hideLabel?: boolean;
    isHorizontal?: boolean;
    fakeFocus?: boolean;
    defaultChecked?: boolean;
    tooltipLabel?: boolean;
    excludeFromICheck?: boolean;
    fullWidth?: boolean;

    tooltip?: string;
    tooltipIcon?: IconType;

    name?: string;
}

export default function CheckBox(props: IProps) {
    const ownInputID = useUniqueID("checkbox");
    const inputID = props.id ?? ownInputID;
    const ownLabelID = useUniqueID("checkbox_label");
    const labelID = props["aria-labelledby"] ?? ownLabelID;
    const classes = checkRadioClasses();

    const {
        isHorizontal,
        fullWidth,
        labelBold = false,
        onChange,
        onBlur,
        checked,
        disabled,
        className,
        fakeFocus,
        excludeFromICheck,
        defaultChecked,
        tooltipLabel,
        label,
        hideLabel,

        tooltip,
        tooltipIcon = "info",

        name,
    } = props;

    const icon = (
        <span className={classes.iconContainer} aria-hidden="true">
            <svg className={classNames(classes.checkIcon)} xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10">
                <title>{t("✓")}</title>
                <path
                    fill="currentColor"
                    d="M10,2.7c0-0.2-0.1-0.3-0.2-0.4L8.9,1.3c-0.2-0.2-0.6-0.2-0.9,0L3.8,5.6L1.9,3.7c-0.2-0.2-0.6-0.2-0.9,0L0.2,4.6c-0.2,0.2-0.2,0.6,0,0.9l3.2,3.2c0.2,0.2,0.6,0.2,0.9,0l5.5-5.5C9.9,3,10,2.8,10,2.7z"
                />
            </svg>
        </span>
    );

    return (
        <label
            className={classNames(
                className,
                classes.root,
                { isHorizontal, minContent: hideLabel },
                fullWidth && classes.fullWidth,
            )}
        >
            <input
                className={classNames(classes.input, fakeFocus && "focus-visible", {
                    "exclude-icheck": excludeFromICheck,
                })}
                aria-labelledby={labelID}
                aria-describedby={props["aria-describedby"]}
                type="checkbox"
                onChange={onChange}
                onBlur={onBlur}
                checked={checked}
                defaultChecked={defaultChecked}
                disabled={disabled}
                tabIndex={0}
                name={name}
                id={inputID}
            />
            {tooltipLabel && label ? <ToolTip label={label}>{icon}</ToolTip> : icon}
            {!!label && (
                <span
                    id={labelID}
                    className={classNames(classes.label, {
                        [classes.labelBold]: labelBold,
                        [visibility().visuallyHidden]: tooltipLabel || hideLabel,
                    })}
                >
                    {label}
                </span>
            )}

            {tooltip && (
                <ToolTip label={tooltip}>
                    <ToolTipIcon>
                        <span className={classes.tooltipIconContainer}>
                            <Icon icon={tooltipIcon} className={classes.tooltipIcon} />
                        </span>
                    </ToolTipIcon>
                </ToolTip>
            )}
        </label>
    );
}
