/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import { IOptionalComponentID } from "../componentIDs";
import { ButtonBaseClass } from "./forms/Button";
import SmartLink from "@library/components/navigation/SmartLink";

interface IProps extends IOptionalComponentID {
    children: React.ReactNode;
    className?: string;
    to: string;
    title?: string;
    ariaLabel?: string;
    baseClass?: ButtonBaseClass;
}

/**
 * A Link component that looks like a Button component.
 */
export default class LinkAsButton extends React.Component<IProps> {
    public static defaultProps: Partial<IProps> = {
        baseClass: ButtonBaseClass.STANDARD,
    };

    public render() {
        const componentClasses = classNames(this.props.baseClass, this.props.className);
        return (
            <SmartLink
                className={componentClasses}
                title={this.props.title}
                aria-label={this.props.ariaLabel || this.props.title}
                tabIndex={-1}
                to={this.props.to}
            >
                {this.props.children}
            </SmartLink>
        );
    }
}
