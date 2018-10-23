/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import { getRequiredID, IOptionalComponentID } from "@library/componentIDs";
import { IFieldError } from "@library/@types/api";
import Paragraph from "@library/components/Paragraph";
import ErrorMessages from "@dashboard/components/forms/ErrorMessages";

export interface IInputTextProps extends IOptionalComponentID {
    className?: string;
    label: string;
    value: string;
    onChange: (event: React.ChangeEvent<HTMLInputElement>) => void;
    labelNote?: string;
    inputClassNames?: string;
    type?: string;
    labelID?: string;
    defaultValue?: string;
    placeholder?: string;
    valid?: boolean;
    descriptionID?: string;
    required?: boolean;
    errors?: IFieldError[];
    disabled?: boolean;
}

interface IState {
    id: string;
}

export default class InputTextBlock extends React.Component<IInputTextProps, IState> {
    public static defaultProps = {
        disabled: false,
        type: "text",
        errors: [],
    };

    private inputRef: React.RefObject<HTMLInputElement> = React.createRef();

    public constructor(props) {
        super(props);
        this.state = {
            id: getRequiredID(props, "inputText") as string,
        };
    }

    public render() {
        const componentClasses = classNames("inputBlock", this.props.className);
        const inputClasses = classNames("inputBlock-inputText", "InputBox", "inputText", this.props.inputClassNames);
        const hasErrors = !!this.props.errors && this.props.errors.length > 0;

        let describedBy;
        if (hasErrors) {
            describedBy = this.errorID;
        }

        return (
            <label className={componentClasses}>
                <span id={this.labelID} className="inputBlock-labelAndDescription">
                    <span className="inputBlock-labelText">{this.props.label}</span>
                    <Paragraph id={""} className="inputBlock-labelNote" children={this.props.labelNote} />
                </span>

                <span className="inputBlock-inputWrap">
                    <input
                        id={this.state.id}
                        className={inputClasses}
                        defaultValue={this.props.defaultValue}
                        value={this.props.value}
                        type={this.props.type}
                        disabled={this.props.disabled}
                        required={this.props.required}
                        placeholder={this.props.placeholder}
                        aria-invalid={hasErrors}
                        aria-describedby={describedBy}
                        aria-labelledby={this.labelID}
                        onChange={this.onChange}
                        ref={this.inputRef}
                    />
                </span>
                <ErrorMessages id={this.errorID} errors={this.props.errors} />
            </label>
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
        if (this.props.onChange) {
            this.props.onChange(event);
        }
    };

    private get labelID(): string {
        return this.state.id + "-label";
    }

    private get errorID(): string {
        return this.state.id + "-errors";
    }
}
