/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import { IOptionalComponentID } from "../componentIDs";
import { ButtonBaseClass } from "./forms/Button";
import SmartLink from "@library/components/navigation/SmartLink";
import { LinkProps } from "react-router-dom";

interface IProps extends IOptionalComponentID, LinkProps {
    children: React.ReactNode;
    className?: string;
    to: string;
    title?: string;
    ariaLabel?: string;
    baseClass?: ButtonBaseClass;
    tabIndex?: number;
}

/**
 * A Link component that looks like a Button component.
 */
export default class LinkAsButton extends React.Component<IProps> {
    public static defaultProps: Partial<IProps> = {
        baseClass: ButtonBaseClass.STANDARD,
        tabIndex: 0,
    };

    public render() {
        const { baseClass, className, title, ariaLabel, to, children, tabIndex, ...restProps } = this.props;
        const componentClasses = classNames(baseClass, className);
        return (
            <SmartLink
                className={componentClasses}
                title={title}
                aria-label={ariaLabel || title}
                tabIndex={tabIndex}
                to={to}
                {...restProps}
            >
                {children}
            </SmartLink>
        );
    }
}
