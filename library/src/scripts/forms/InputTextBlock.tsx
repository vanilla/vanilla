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

export enum InputTextBlockBaseClass {
    STANDARD = "inputBlock",
    CUSTOM = "",
}

export interface IInputTextProps extends Omit<IInputBlockProps, "children"> {
    inputProps: {
        value?: string;
        onChange?: (event: React.ChangeEvent<HTMLInputElement>) => void;
        onKeyPress?: React.KeyboardEventHandler;
        inputClassNames?: string;
        type?: string;
        defaultValue?: string;
        placeholder?: string;
        valid?: boolean;
        required?: boolean;
        disabled?: boolean;
        inputRef?: React.RefObject<HTMLInputElement>;
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
    private ownInputRef: React.RefObject<HTMLInputElement> = React.createRef();
    private get inputRef() {
        return this.props.inputProps.inputRef || this.ownInputRef;
    }

    public constructor(props) {
        super(props);
        this.id = getRequiredID(props, "inputText");
    }

    public render() {
        const classesInput = inputClasses();
        const classesInputBlock = inputBlockClasses();

        const { inputProps, ...blockProps } = this.props;
        const classes = classNames(classesInputBlock.inputText, "inputText", inputProps.inputClassNames, {
            InputBox: this.props.legacyMode,
            [classesInput.text]: !this.props.legacyMode,
        });

        return (
            <InputBlock {...blockProps} className={classesInputBlock.root}>
                {blockParams => {
                    const { labelID, errorID, hasErrors } = blockParams;
                    let describedBy;
                    if (hasErrors) {
                        describedBy = errorID;
                    }
                    return (
                        <input
                            id={this.id}
                            className={classes}
                            defaultValue={inputProps.defaultValue}
                            value={inputProps.value}
                            type={inputProps.type}
                            disabled={inputProps.disabled}
                            required={inputProps.required}
                            placeholder={inputProps.placeholder}
                            aria-invalid={hasErrors}
                            aria-describedby={describedBy}
                            aria-labelledby={labelID}
                            onChange={this.onChange}
                            ref={this.inputRef}
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
    public componentWillUnount() {
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
        if (this.props.inputProps.onChange) {
            this.props.inputProps.onChange(event);
        }
    };
}
