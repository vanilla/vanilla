/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { IOptionalComponentID, useUniqueID } from "@library/utility/idUtils";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { cx } from "@emotion/css";
import { getClassForButtonType } from "./Button.getClassForButtonType";

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
