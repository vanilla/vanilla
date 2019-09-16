/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { getOptionalID, IOptionalComponentID } from "@library/utility/idUtils";
import { buttonClasses, ButtonTypes, buttonUtilityClasses } from "@library/forms/buttonStyles";
import classNames from "classnames";
import { titleBarClasses } from "@library/headers/titleBarStyles";

interface IProps extends IOptionalComponentID {
    children: React.ReactNode;
    className?: string;
    disabled?: boolean;
    prefix?: string;
    legacyMode?: boolean;
    onClick?: (e) => void;
    onKeyDown?: (e) => void;
    title?: string;
    submit?: boolean;
    ariaLabel?: string;
    baseClass?: ButtonTypes;
    ariaHidden?: boolean;
    tabIndex?: number;
    lang?: string;
    buttonRef?: React.RefObject<HTMLButtonElement>;
    role?: string;
    onKeyDownCapture?: (event: any) => void;
}

interface IState {
    id?: string;
}

export const getButtonStyleFromBaseClass = (type: ButtonTypes | undefined) => {
    if (type) {
        const buttonUtils = buttonUtilityClasses();
        const classes = buttonClasses();
        switch (type) {
            case ButtonTypes.STANDARD:
                return classes.standard;
            case ButtonTypes.TEXT:
                return classes.text;
            case ButtonTypes.TEXT_PRIMARY:
                return classes.textPrimary;
            case ButtonTypes.ICON:
                return buttonUtils.buttonIcon;
            case ButtonTypes.ICON_COMPACT:
                return buttonUtils.buttonIconCompact;
            case ButtonTypes.PRIMARY:
                return classes.primary;
            case ButtonTypes.TRANSPARENT:
                return classes.transparent;
            case ButtonTypes.TRANSLUCID:
                return classes.translucid;
            case ButtonTypes.TITLEBAR_LINK:
                return titleBarClasses().linkButton;
            case ButtonTypes.CUSTOM:
                return classes.custom;
            case ButtonTypes.RESET:
                return buttonUtilityClasses().reset;
            case ButtonTypes.DASHBOARD_STANDARD:
                return "btn";
            case ButtonTypes.DASHBOARD_PRIMARY:
                return "btn btn-primary";
            case ButtonTypes.DASHBOARD_SECONDARY:
                return "btn btn-secondary";
            case ButtonTypes.DASHBOARD_LINK:
                return "btn btn-link";
            default:
                return "";
        }
    } else {
        return "";
    }
};

/**
 * A stylable, configurable button component.
 */
export default class Button extends React.Component<IProps, IState> {
    public static defaultProps: Partial<IProps> = {
        id: undefined,
        disabled: false,
        prefix: "button",
        legacyMode: false,
        baseClass: ButtonTypes.STANDARD,
    };

    constructor(props) {
        super(props);
        this.state = {
            id: getOptionalID(props, props.prefix) as string | undefined,
        };
    }

    public render() {
        const componentClasses = classNames(
            getButtonStyleFromBaseClass(this.props.baseClass),
            { Button: this.props.legacyMode },
            this.props.className,
        );

        return (
            <button
                id={this.state.id}
                disabled={this.props.disabled}
                type={this.props.submit ? "submit" : "button"}
                className={componentClasses}
                onClick={this.props.onClick}
                title={this.props.title}
                aria-label={this.props.ariaLabel || this.props.title}
                aria-hidden={this.props.ariaHidden}
                tabIndex={this.props.tabIndex}
                ref={this.props.buttonRef}
                onKeyDown={this.props.onKeyDown}
                lang={this.props.lang}
                role={this.props.role}
                onKeyDownCapture={this.props.onKeyDownCapture}
            >
                {this.props.children}
            </button>
        );
    }
}
