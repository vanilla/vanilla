/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import { getOptionalID, IOptionalComponentID } from "../../componentIDs";
import { ButtonBaseClass } from "./Button";
import { Link } from "react-router-dom";

interface IProps extends IOptionalComponentID {
    children: React.ReactNode;
    className?: string;
    to: string;
    title?: string;
    ariaLabel?: string;
    baseClass?: ButtonBaseClass;
}

/**
 * A stylable, configurable button component.
 */
export default class LinkAsButton extends React.Component<IProps> {
    public static defaultProps = {
        disabled: false,
        type: "button",
        prefix: "button",
        legacyMode: false,
        baseClass: ButtonBaseClass.STANDARD,
    };

    public render() {
        const componentClasses = classNames(this.props.baseClass, this.props.className);
        return (
            <Link
                className={componentClasses}
                title={this.props.title}
                aria-label={this.props.ariaLabel || this.props.title}
                tabIndex={-1}
                to={this.props.to}
            >
                {this.props.children}
            </Link>
        );
    }
}
