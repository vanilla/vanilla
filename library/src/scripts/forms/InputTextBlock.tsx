/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import InputBlock, { IInputBlockProps } from "@library/forms/InputBlock";
import { inputBlockClasses } from "@library/forms/InputBlockStyles";
import { inputClasses } from "@library/forms/inputStyles";
import { useUniqueID } from "@library/utility/idUtils";
import classNames from "classnames";
import { Property } from "csstype";
import React, { InputHTMLAttributes, useEffect, useRef } from "react";
import { TextareaAutosize } from "react-autosize-textarea/lib/TextareaAutosize";

export enum InputTextBlockBaseClass {
    STANDARD = "inputBlock",
    CUSTOM = "",
}

export interface IInputProps {
    value?: string | number;
    onFocus?: (event: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => void;
    onBlur?: (event: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => void;
    onChange?: (event: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => void;
    onKeyPress?: React.KeyboardEventHandler;
    inputClassNames?: string;
    type?: string;
    defaultValue?: string;
    placeholder?: string;
    valid?: boolean;
    required?: boolean;
    disabled?: boolean;
    inputRef?: React.RefObject<HTMLInputElement | HTMLTextAreaElement>;
    multiline?: boolean;
    minLength?: number;
    maxLength?: number;
    className?: string;
    autoComplete?: boolean;
    "aria-label"?: string;
    "aria-describedby"?: string;
    // these are for number inputs
    min?: number;
    max?: number;
    step?: number;
    inputmode?: string;
    pattern?: string;
}

export interface IInputTextProps extends Omit<IInputBlockProps, "children" | "legend"> {
    inputProps?: IInputProps;
    multiLineProps?: {
        onResize?: (event) => {};
        rows?: number;
        maxRows?: number;
        async?: boolean;
        resize?: Property.Resize; // for textarea only
        overflow?: Property.Overflow; // for textarea only
        className?: string;
    };
}

export default function InputTextBlock(props: IInputTextProps) {
    const ownID = useUniqueID("inputText");
    const id = props.id ?? ownID;

    const classesInput = inputClasses.useAsHook();
    const classesInputBlock = inputBlockClasses.useAsHook();

    const { legacyMode = false, inputProps = {}, multiLineProps = {}, ...blockProps } = props;
    const classes = classNames(classesInputBlock.inputText, inputProps.inputClassNames, {
        InputBox: legacyMode,
        [classesInput.text]: !legacyMode,
    });

    const ownRef = useRef<HTMLInputElement | HTMLTextAreaElement>(null);
    const inputRef = inputProps.inputRef || ownRef;

    const onChange = props.inputProps?.onChange;

    useEffect(() => {
        // Use a native change event instead of React's because of https://github.com/facebook/react/issues/1159
        if (onChange) {
            inputRef.current?.addEventListener("change", onChange as any);
            return () => {
                inputRef.current?.removeEventListener("change", onChange as any);
            };
        }
    }, [onChange]);

    return (
        <InputBlock {...blockProps} className={classNames(classesInputBlock.root, props.className)}>
            {(blockParams) => {
                const { errorID, hasErrors } = blockParams;
                let describedBy = inputProps["aria-describedby"];
                if (hasErrors) {
                    describedBy = errorID;
                }

                const commonProps = {
                    id: id,
                    defaultValue: inputProps.defaultValue,
                    value: inputProps.value,
                    type: inputProps.type ?? "text",
                    disabled: inputProps.disabled ?? false,
                    required: inputProps.required,
                    placeholder: inputProps.placeholder,
                    maxLength: inputProps.maxLength,
                    min: inputProps.min,
                    max: inputProps.max,
                    step: inputProps.step,
                    onChange: inputProps.onChange,
                    onFocus: inputProps.onFocus,
                    onBlur: inputProps.onBlur,
                    ref: inputRef,
                    onKeyPress: inputProps.onKeyPress,
                    "aria-invalid": hasErrors,
                    "aria-describedby": describedBy,
                    "aria-label": inputProps["aria-label"],
                    inputMode: inputProps.inputmode,
                    pattern: inputProps.pattern,
                };

                return !inputProps.multiline ? (
                    <input
                        {...(commonProps as InputHTMLAttributes<HTMLInputElement>)}
                        className={classNames(classes, inputProps.className)}
                        autoComplete={inputProps.autoComplete ? "on" : "off"}
                    />
                ) : (
                    <TextareaAutosize
                        {...(commonProps as InputHTMLAttributes<HTMLTextAreaElement>)}
                        {...multiLineProps}
                        async
                        className={classNames(classes, multiLineProps.className, {
                            [classesInputBlock.multiLine(
                                multiLineProps.resize ? multiLineProps.resize : "none",
                                multiLineProps.overflow,
                            )]: inputProps.multiline,
                        })}
                    />
                );
            }}
        </InputBlock>
    );
}
