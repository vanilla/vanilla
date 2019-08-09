/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import { getOptionalID, IOptionalComponentID } from "@library/utility/idUtils";

export interface IParagraphProps extends IOptionalComponentID {
    className?: string;
    isError?: boolean;
    children?: React.ReactNode;
}

export default class Paragraph extends React.Component<IParagraphProps> {
    public static defaultProps = {
        id: false,
    };

    public id: string;

    constructor(props) {
        super(props);
        this.id = getOptionalID(props, "Paragraph") as string;
    }

    public render() {
        if (this.props.children) {
            const componentClasses = classNames({ isError: this.props.isError }, this.props.className);

            let accessibilityProps = {};

            if (this.props.isError) {
                accessibilityProps = {
                    "aria-live": "assertive",
                    role: "alert",
                };
            }

            return (
                <p id={this.id} className={componentClasses} {...accessibilityProps}>
                    {this.props.children}
                </p>
            );
        } else {
            return null;
        }
    }
}
