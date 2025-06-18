/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { cx } from "@emotion/css";
import { IError } from "@library/errorPages/CoreErrorMessages";
import ErrorMessages from "@library/forms/ErrorMessages";
import { inputBlockClasses } from "@library/forms/InputBlockStyles";
import Paragraph from "@library/layout/Paragraph";
import { ToolTip, ToolTipIcon } from "@library/toolTip/ToolTip";
import { IOptionalComponentID, useUniqueID } from "@library/utility/idUtils";
import { t } from "@vanilla/i18n";
import { Icon, IconType } from "@vanilla/icons";
import classNames from "classnames";
import React, { ReactNode } from "react";

export enum InputTextBlockBaseClass {
    STANDARD = "inputBlock",
    CUSTOM = "",
}

interface ICallbackProps {
    labelID: string;
    hasErrors: boolean;
    errorID: string;
}
type CallbackChildren = (props: ICallbackProps) => React.ReactNode;

export interface IInputBlockProps extends IOptionalComponentID {
    label?: ReactNode;
    legend?: ReactNode;
    children: React.ReactNode | CallbackChildren;
    className?: string;
    wrapClassName?: string;
    labelClassName?: string;
    noteAfterInput?: string | ReactNode;
    labelNote?: ReactNode;
    labelID?: string;
    errors?: IError[];
    baseClass?: InputTextBlockBaseClass;
    legacyMode?: boolean;
    noMargin?: boolean;
    grid?: boolean;
    tight?: boolean;
    extendErrorMessage?: boolean;
    required?: boolean;
    tooltip?: string;
    tooltipIcon?: IconType;
}

export default function InputBlock(props: IInputBlockProps) {
    const {
        label,
        errors = [],
        baseClass = InputTextBlockBaseClass.STANDARD,
        legend,
        required,
        tooltip,
        tooltipIcon = "info",
    } = props;
    const OuterTag = legend ? "div" : label ? "label" : "div";
    const role = legend ? "group" : undefined;

    const LegendOrSpanTag = legend ? "div" : "span";

    const hasLegendOrLabel = !!props.legend || !!props.label;

    const classesInputBlock = inputBlockClasses.useAsHook();
    const componentClasses = classNames(
        baseClass === InputTextBlockBaseClass.STANDARD ? classesInputBlock.root : "",
        props.className,
    );
    const hasErrors = !!errors && errors.length > 0;

    const ownId = useUniqueID("inputBlock");
    const id = props.id ?? ownId;
    const labelID = props.labelID ?? id + "-label";
    const errorID = id + "-errors";

    let children;
    if (typeof props.children === "function") {
        // Type is checked, but typechecker not accepting it.
        // eslint-disable-next-line @typescript-eslint/ban-types
        children = (props.children as Function)({ hasErrors, errorID: errorID, labelID: labelID });
    } else {
        children = props.children;
    }

    return (
        <OuterTag className={componentClasses} role={role} aria-labelledby={role === "group" ? labelID : undefined}>
            {hasLegendOrLabel && (
                <span className={classesInputBlock.labelAndDescription}>
                    <LegendOrSpanTag
                        id={labelID}
                        className={classNames(classesInputBlock.labelText, props.labelClassName)}
                    >
                        {required && (
                            <span aria-label={t("required")} className={classesInputBlock.labelRequired}>
                                *
                            </span>
                        )}
                        {props.legend ?? props.label!}

                        {tooltip && (
                            <ToolTip label={tooltip}>
                                <ToolTipIcon>
                                    <span className={classesInputBlock.tooltipIconContainer}>
                                        <Icon className={classesInputBlock.tooltipIcon} icon={tooltipIcon} />
                                    </span>
                                </ToolTipIcon>
                            </ToolTip>
                        )}
                    </LegendOrSpanTag>
                    <Paragraph className={classesInputBlock.labelNote}>{props.labelNote}</Paragraph>
                </span>
            )}

            <span
                className={classNames(
                    classesInputBlock.inputWrap,
                    [classesInputBlock.fieldsetGroup],
                    props.wrapClassName,
                    { [classesInputBlock.grid]: props.grid },
                    { [classesInputBlock.tight]: props.tight },
                    { noMargin: props.noMargin },
                )}
            >
                {children}
            </span>
            <Paragraph className={classesInputBlock.noteAfterInput}>{props.noteAfterInput}</Paragraph>
            <ErrorMessages
                id={errorID}
                errors={errors}
                className={cx({ [classesInputBlock.extendErrorPadding]: props.extendErrorMessage })}
                padded
            />
        </OuterTag>
    );
}
