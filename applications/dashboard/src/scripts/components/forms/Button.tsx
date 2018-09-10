/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import classNames from "classnames";
import { getOptionalID, IOptionalComponentID } from "@library/componentIDs";

interface IProps extends IOptionalComponentID {
    className?: string;
    type: string;
    content: string | Node;
    disabled?: boolean;
    prefix: string;
}

interface IState {
    id?: string;
}

export default class Button extends React.Component<IProps, IState> {
    public static defaultProps = {
        id: false,
        disabled: false,
        type: "button",
        prefix: "button",
    };

    constructor(props) {
        super(props);
        this.state = {
            id: getOptionalID(props, props.prefix) as string | undefined,
        };
    }

    public render() {
        const componentClasses = classNames("button", "Button", this.props.className);

        return (
            <button
                id={this.state.id}
                disabled={this.props.disabled}
                type={this.props.type}
                className={componentClasses}
            >
                {this.props.content}
            </button>
        );
    }
}
