/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { inputClasses } from "@library/forms/inputStyles";
import InputBlock, { IInputBlockProps } from "@library/forms/InputBlock";
import { getRequiredID } from "@library/utility/idUtils";
import { Omit } from "@library/@types/utils";
import classNames from "classnames";
import { inputBlockClasses } from "@library/forms/InputBlockStyles";
import { OverflowProperty, ResizeProperty } from "csstype";
import { TextareaAutosize } from "react-autosize-textarea/lib/TextareaAutosize";

export enum InputTextBlockBaseClass {
    STANDARD = "inputBlock",
    CUSTOM = "",
}

export interface IInputProps {
    value?: string;
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
    maxLength?: number;
    className?: string;
}

export interface IInputTextProps extends Omit<IInputBlockProps, "children"> {
    inputProps?: IInputProps;
    multiLineProps?: {
        onResize?: (event) => {};
        rows?: number;
        maxRows?: number;
        async?: boolean;
        resize?: ResizeProperty; // for textarea only
        overflow?: OverflowProperty; // for textarea only
        className?: string;
    };
}

export default class InputTextBlock extends React.Component<IInputTextProps> {
    public static defaultProps = {
        disabled: false,
        type: "text",
        errors: [],
        baseClass: InputTextBlockBaseClass.STANDARD,
        legacyMode: false,
    };

    private id: string;
    private ownInputRef = React.createRef<HTMLInputElement | HTMLTextAreaElement>();
    private get inputRef() {
        const { inputProps = {} } = this.props;
        return inputProps.inputRef || this.ownInputRef;
    }

    public constructor(props) {
        super(props);
        this.id = getRequiredID(props, "inputText");
    }

    public render() {
        const classesInput = inputClasses();
        const classesInputBlock = inputBlockClasses();

        const { inputProps = {}, multiLineProps = {}, ...blockProps } = this.props;
        const classes = classNames(classesInputBlock.inputText, "inputText", inputProps.inputClassNames, {
            InputBox: this.props.legacyMode,
            [classesInput.text]: !this.props.legacyMode,
        });

        return (
            <InputBlock {...blockProps} className={classNames(classesInputBlock.root, this.props.className)}>
                {blockParams => {
                    const { labelID, errorID, hasErrors } = blockParams;
                    let describedBy;
                    if (hasErrors) {
                        describedBy = errorID;
                    }

                    return !inputProps.multiline ? (
                        <input
                            id={this.id}
                            className={classNames(classes, inputProps.className)}
                            defaultValue={inputProps.defaultValue}
                            value={inputProps.value}
                            type={inputProps.type}
                            disabled={inputProps.disabled}
                            required={inputProps.required}
                            placeholder={inputProps.placeholder}
                            aria-invalid={hasErrors}
                            aria-describedby={describedBy}
                            aria-labelledby={labelID}
                            maxLength={inputProps.maxLength}
                            onChange={this.onChange}
                            ref={this.inputRef as any} // Typescripts ref checking a little ridiculous. Distinction without a difference.
                            onKeyPress={inputProps.onKeyPress}
                        />
                    ) : (
                        <TextareaAutosize
                            {...multiLineProps}
                            id={this.id}
                            className={classNames(classes, multiLineProps.className, {
                                [classesInputBlock.multiLine(
                                    multiLineProps.resize ? multiLineProps.resize : "none",
                                    multiLineProps.overflow,
                                )]: inputProps.multiline,
                            })}
                            defaultValue={inputProps.defaultValue}
                            value={inputProps.value}
                            type={inputProps.type}
                            disabled={inputProps.disabled}
                            required={inputProps.required}
                            placeholder={inputProps.placeholder}
                            aria-invalid={hasErrors}
                            aria-describedby={describedBy}
                            aria-labelledby={labelID}
                            maxLength={inputProps.maxLength}
                            onChange={this.onChange}
                            ref={this.inputRef as any} // Typescripts ref checking a little ridiculous. Distinction without a difference.
                            onKeyPress={inputProps.onKeyPress}
                        />
                    );
                }}
            </InputBlock>
        );
    }

    /**
     * Use a native change event instead of React's because of https://github.com/facebook/react/issues/1159
     */
    public componentDidMount() {
        this.inputRef.current!.addEventListener("change", this.onChange);
    }

    /**
     * Use a native change event instead of React's because of https://github.com/facebook/react/issues/1159
     */
    public componentWillUnmount() {
        this.inputRef.current!.removeEventListener("change", this.onChange);
    }

    public get value(): any {
        return this.inputRef.current ? this.inputRef.current.value : "";
    }

    public set value(value) {
        if (this.inputRef.current) {
            this.inputRef.current.value = value;
        } else {
            throw new Error("inputDom does not exist");
        }
    }

    public focus() {
        this.inputRef.current!.focus();
    }

    public select() {
        this.inputRef.current!.select();
    }

    private onChange = event => {
        const { inputProps = {} } = this.props;
        if (inputProps.onChange) {
            inputProps.onChange(event);
        }
    };
}
