/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { getClassForButtonType } from "@library/forms/Button";
import SmartLink from "@library/routing/links/SmartLink";
import { IOptionalComponentID } from "@library/utility/idUtils";
import { LinkProps } from "react-router-dom";
import ConditionalWrap from "@library/layout/ConditionalWrap";
import { buttonLabelWrapClass } from "@library/forms/buttonStyles";

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
export default class LinkAsButton extends React.Component<IProps> {
    public static defaultProps: Partial<IProps> = {
        tabIndex: 0,
    };

    public render() {
        const {
            buttonType = ButtonTypes.STANDARD,
            className,
            title,
            ariaLabel,
            to,
            children,
            tabIndex,
            addWrap = false,
            disabled,
            ...restProps
        } = this.props;
        const componentClasses = classNames(getClassForButtonType(buttonType), className);

        const fallbackTitle = typeof children === "string" ? children : undefined;
        return (
            <SmartLink
                className={componentClasses}
                title={title ?? fallbackTitle}
                aria-label={ariaLabel || title || fallbackTitle}
                tabIndex={tabIndex}
                role={"button"}
                to={disabled ? "" : to}
                disabled={disabled}
                {...restProps}
            >
                <ConditionalWrap className={buttonLabelWrapClass().root} condition={addWrap}>
                    {children}
                </ConditionalWrap>
            </SmartLink>
        );
    }
}
