/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import { getOptionalID, IOptionalComponentID } from "../componentIDs";

interface IProps extends IOptionalComponentID {
    className?: string;
    isError?: boolean;
    content?: React.ReactNode;
}

export default class Paragraph extends React.Component<IProps> {
    public static defaultProps = {
        id: false,
    };

    public id: string;

    constructor(props) {
        super(props);
        this.id = getOptionalID(props, "Paragraph") as string;
    }

    public render() {
        if (this.props.content) {
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
                    {this.props.content}
                </p>
            );
        } else {
            return null;
        }
    }
}
