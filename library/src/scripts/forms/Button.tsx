/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { getOptionalID, IOptionalComponentID } from "@library/utility/idUtils";
import { buttonClasses, buttonUtilityClasses } from "../forms/buttonStyles";
import classNames from "classnames";
import { titleBarClasses } from "@library/headers/titleBarStyles";
import { ButtonTypes } from "@library/forms/buttonTypes";

export interface IButtonProps extends IOptionalComponentID, React.HTMLAttributes<HTMLButtonElement> {
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
    buttonRef?: React.Ref<HTMLButtonElement>;
    role?: string;
    onKeyDownCapture?: (event: any) => void;
    controls?: string;
    style?: {};
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
            case ButtonTypes.NOT_STANDARD:
                return classes.notStandard;
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
export default class Button extends React.Component<IButtonProps, IState> {
    public static defaultProps: Partial<IButtonProps> = {
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
        const {
            baseClass,
            legacyMode,
            className,
            id,
            submit,
            ariaLabel,
            ariaHidden,
            controls,
            buttonRef,
            ...restProps
        } = this.props;
        const componentClasses = classNames(getButtonStyleFromBaseClass(baseClass), { Button: legacyMode }, className);

        return (
            <button
                id={this.state.id}
                type={submit ? "submit" : "button"}
                className={componentClasses}
                aria-label={ariaLabel ?? restProps.title}
                aria-hidden={ariaHidden}
                ref={buttonRef}
                aria-controls={controls}
                {...restProps}
            >
                {this.props.children}
            </button>
        );
    }
}
