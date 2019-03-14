/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import Button from "Button";
import { IOptionalComponentID } from "../utility/idUtils";
import { ButtonTypes } from "@library/styles/buttonStyles";

interface IProps extends IOptionalComponentID {
    children: React.ReactNode;
    className?: string;
    disabled?: boolean;
    legacyMode?: boolean;
    baseClass?: ButtonTypes;
    tabIndex?: number;
}

export default class ButtonSubmit extends React.Component<IProps, IOptionalComponentID> {
    public static defaultProps = {
        disabled: false,
        legacyMode: false,
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
                className={componentClasses}
                prefix="submitButton"
                legacyMode={this.props.legacyMode}
                baseClass={this.props.baseClass}
                tabIndex={this.props.tabIndex}
            >
                {this.props.children}
            </Button>
        );
    }
}
