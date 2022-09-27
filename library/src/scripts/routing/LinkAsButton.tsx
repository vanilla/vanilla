/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useMemo } from "react";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { getClassForButtonType } from "@library/forms/Button";
import SmartLink from "@library/routing/links/SmartLink";
import { IOptionalComponentID } from "@library/utility/idUtils";
import { LinkProps } from "react-router-dom";
import ConditionalWrap from "@library/layout/ConditionalWrap";
import { buttonLabelWrapClass } from "@library/forms/Button.styles";
import { cx } from "@emotion/css";

interface IProps extends IOptionalComponentID, LinkProps {
    children: React.ReactNode;
    className?: string;
    to: string;
    title?: string;
    ariaLabel?: string;
    buttonType?: ButtonTypes;
    tabIndex?: number;
    disabled?: boolean;
    addWrap?: boolean; // Adds wrapper class to help with overflowing text
}

/**
 * A Link component that looks like a Button component.
 */
export function LinkAsButton(props: IProps) {
    const {
        buttonType = ButtonTypes.STANDARD,
        className,
        title,
        ariaLabel,
        to,
        children,
        tabIndex = 0,
        addWrap = false,
        disabled,
        ...restProps
    } = props;
    const componentClasses = cx(getClassForButtonType(buttonType), className);

    const fallbackTitle = typeof children === "string" ? children : undefined;

    const linkProps = useMemo(() => {
        return {
            className: componentClasses,
            title: title ?? fallbackTitle,
            "aria-label": ariaLabel || title || fallbackTitle,
            tabIndex,
            role: !disabled ? "button" : "presentation",
            disabled,
        };
    }, [ariaLabel, componentClasses, disabled, fallbackTitle, tabIndex, title]);

    // Disabled links are invalid. Therefore the component returns span wrapped text instead
    if (disabled) {
        return (
            <span {...linkProps} {...restProps}>
                <ConditionalWrap className={buttonLabelWrapClass().root} condition={addWrap}>
                    {children}
                </ConditionalWrap>
            </span>
        );
    }

    return (
        <SmartLink {...linkProps} to={to ?? ""} {...restProps}>
            <ConditionalWrap className={buttonLabelWrapClass().root} condition={addWrap}>
                {children}
            </ConditionalWrap>
        </SmartLink>
    );
}

export default LinkAsButton;
