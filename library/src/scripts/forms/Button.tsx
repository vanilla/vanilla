/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { IOptionalComponentID, useUniqueID } from "@library/utility/idUtils";
import { ButtonType, ButtonTypes } from "@library/forms/buttonTypes";
import { cx } from "@emotion/css";
import { getClassForButtonType } from "./Button.getClassForButtonType";
import { useWithThemeContext } from "@library/theming/ThemeOverrideContext";
import type { UseMutationResult } from "@tanstack/react-query";
import ButtonLoader from "@library/loaders/ButtonLoader";

export interface IButtonProps extends IOptionalComponentID, React.ButtonHTMLAttributes<HTMLButtonElement> {
    prefix?: string;
    legacyMode?: boolean;
    ariaLabel?: string;
    buttonType?: ButtonType;
    ariaHidden?: boolean;
    tabIndex?: number;
    buttonRef?: React.Ref<HTMLButtonElement>;
    controls?: string;
    submit?: boolean;
    mutation?: UseMutationResult<any>;
}

interface IState {
    id?: string;
}

declare namespace Button {
    export type Type = ButtonType;
}

/**
 * A stylable, configurable button component.
 */
const ButtonInit = React.forwardRef(function Button(_props: IButtonProps, ref: React.Ref<HTMLButtonElement>) {
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
        mutation,
        ...restProps
    } = props;

    const ownID = useUniqueID(props.prefix);
    const id = _id ?? ownID;
    const componentClasses = useWithThemeContext(() =>
        cx(getClassForButtonType(buttonType), { Button: legacyMode }, className),
    );

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
            disabled={props.disabled ?? mutation?.isLoading ?? false}
        >
            {mutation?.isLoading ? <ButtonLoader /> : props.children}
        </button>
    );
});

const Button = Object.assign(ButtonInit, {
    Type: ButtonType,
    getClassForType: getClassForButtonType,
    Loader: ButtonLoader,
});

export default Button;
