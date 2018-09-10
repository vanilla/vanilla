/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import classNames from "classnames";
import { IOptionalComponentID } from "@library/componentIDs";
import Button from "@dashboard/components/forms/Button";

interface IProps extends IOptionalComponentID {
    content: string | Node;
    className?: string;
    disabled?: boolean;
}

export default class ButtonSubmit extends React.Component<IProps, IOptionalComponentID> {
    public static defaultProps = {
        disabled: false,
    };

    constructor(props) {
        super(props);
    }

    public render() {
        const componentClasses = classNames(
            "Primary",
            "buttonCTA",
            "BigButton",
            "button-fullWidth",
            this.props.className,
        );

        return (
            <Button
                id={this.props.id}
                disabled={this.props.disabled}
                type="submit"
                content={this.props.content}
                className={componentClasses}
                prefix="submitButton"
            >
                {this.props.content}
            </Button>
        );
    }
}
