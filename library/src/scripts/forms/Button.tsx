/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { IOptionalComponentID, useUniqueID } from "@library/utility/idUtils";
import { buttonClasses, buttonUtilityClasses } from "./Button.styles";
import { titleBarClasses } from "@library/headers/titleBarStyles";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { cx } from "@emotion/css";

export interface IButtonProps extends IOptionalComponentID, React.ButtonHTMLAttributes<HTMLButtonElement> {
    prefix?: string;
    legacyMode?: boolean;
    ariaLabel?: string;
    buttonType?: ButtonTypes;
    ariaHidden?: boolean;
    tabIndex?: number;
    buttonRef?: React.Ref<HTMLButtonElement>;
    controls?: string;
    submit?: boolean;
}

interface IState {
    id?: string;
}

export const getClassForButtonType = (type: ButtonTypes | undefined) => {
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
            case ButtonTypes.ICON_MENUBAR:
                return buttonUtils.buttonIconMenuBar;
            case ButtonTypes.ICON_COMPACT:
                return buttonUtils.buttonIconCompact;
            case ButtonTypes.PRIMARY:
                return classes.primary;
            case ButtonTypes.TRANSPARENT:
                return classes.transparent;
            case ButtonTypes.OUTLINE:
                return classes.outline;
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
const Button = React.forwardRef(function Button(_props: IButtonProps, ref: React.Ref<HTMLButtonElement>) {
    const props = {
        id: undefined,
        disabled: false,
        prefix: "button",
        legacyMode: false,
        buttonType: ButtonTypes.STANDARD,
        ..._props,
    };
    const {
        buttonType,
        legacyMode,
        className,
        submit,
        ariaLabel,
        ariaHidden,
        controls,
        buttonRef,
        id: _id,
        ...restProps
    } = props;

    const ownID = useUniqueID(props.prefix);
    const id = _id ?? ownID;
    const componentClasses = cx(getClassForButtonType(buttonType), { Button: legacyMode }, className);

    return (
        <button
            id={id}
            type={submit ? "submit" : "button"}
            className={componentClasses}
            aria-label={ariaLabel ?? restProps.title}
            aria-hidden={ariaHidden}
            ref={ref ?? buttonRef}
            aria-controls={controls}
            {...restProps}
        >
            {props.children}
        </button>
    );
});
export default Button;
