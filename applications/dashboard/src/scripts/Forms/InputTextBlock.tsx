import { t } from '@core/application';
import React from 'react';
import classNames from 'classnames';
import ErrorMessages from "./ErrorMessages";
import { log, logError, debug } from "@core/utility";
import Paragraph from "./Paragraph";
import {uniqueIDFromPrefix, getRequiredID, IRequiredComponentID} from '@core/Interfaces/componentIDs';

export interface IInputTextProps extends IRequiredComponentID {
    className?: string;
    label: string;
    labelNote?: string;
    inputClassNames?: string;
    type?: string;
    labelID?: string;
    value: string;
    placeholder?: string;
    valid?: boolean;
    descriptionID?: string;
    defaultValue?: string;
    required?: boolean;
    errors?: string | string[];
    disabled?: boolean;
    onChange?: any;
}

interface IState {
    id: string;
}

export default class InputTextBlock extends React.Component<IInputTextProps, IState> {
    public static defaultProps = {
        disabled: false,
        type: 'text',
        errors: []
    };

    private inputDom: HTMLInputElement | null;

    public constructor(props) {
        super(props);
        this.state = {
            id: getRequiredID(props, "inputText") as string,
        };
    }

    private handleInputChange(e) {
        // Clear error state.
        this.props.onChange(e);
    }

    private get labelID():string {
        return this.state.id + "-label";
    }

    private get errorID():string {
        return this.state.id + "-errors";
    }

    public get value(): any {
        return this.inputDom ? this.inputDom.value : '';
    }

    public set value(value) {
        if (this.inputDom) {
            this.inputDom.value = value;
        } else {
            throw new Error("inputDom does not exist");
        }
    }

    public render() {
        const componentClasses = classNames(
            'inputBlock',
            this.props.className
        );

        const inputClasses = classNames(
            'inputBlock-inputText',
            'InputBox',
            'inputText',
            this.props.inputClassNames
        );

        const hasErrors = !!this.props.errors && this.props.errors.length > 0;

        let describedBy;
        if (hasErrors) {
            describedBy = this.errorID;
        }

        return <label className={componentClasses}>
            <span id={this.labelID} className="inputBlock-labelAndDescription">
                <span className="inputBlock-labelText">
                    {this.props.label}
                </span>
                <Paragraph id={false} className='inputBlock-labelNote' content={this.props.labelNote}/>
            </span>

            <span className="inputBlock-inputWrap">
                <input
                    id={this.state.id}
                    className={inputClasses}
                    value={this.props.value}
                    type={this.props.type}
                    disabled={this.props.disabled}
                    required={this.props.required}
                    placeholder={this.props.placeholder}
                    aria-invalid={hasErrors}
                    aria-describedby={describedBy}
                    aria-labelledby={this.labelID}
                    onChange={(e) => this.handleInputChange(e)}
                    defaultValue={this.props.defaultValue}
                    ref={inputDom => this.inputDom = inputDom}
                />
            </span>
            <ErrorMessages id={this.errorID} errors={this.props.errors as string | string[]}/>
        </label>;
    }
}
