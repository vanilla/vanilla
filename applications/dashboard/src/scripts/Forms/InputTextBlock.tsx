import { t } from '@core/application';
import React from 'react';
import classNames from 'classnames';
import ErrorMessages from "./ErrorMessages";
import Paragraph from "./Paragraph";
import {getUniqueID, IComponentID} from '@core/Interfaces/componentIDs';

export interface IInputTextProps extends IComponentID{
    className?: string;
    label: string;
    labelNote?: string;
    inputClassNames?: string;
    type?: string;
    labelID?: string;
    value?: string;
    placeholder?: string;
    valid?: boolean;
    descriptionID?: string;
    required?: boolean;
    errors?: string[];
    disabled?: boolean;
}

interface IState {
    disabled: boolean;
    valid?: boolean;
    value?: string;
    errors?: string[];
}

export default class InputTextBlock extends React.Component<IInputTextProps, IState> {
    public ID: string;
    public errorID: string;
    public labelID: string;
    public type: string;

    constructor(props) {
        super(props);
        this.ID = getUniqueID(props, "inputText");
        this.labelID = this.ID + "-label";
        this.errorID = this.ID + "-errors";
        this.type = props.type || 'text';
        this.state = {
            value: props.value || '',
            disabled: props.disabled || false,
            errors: props.errors,
        };
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

        const hasErrors = this.state.errors && this.state.errors.length > 0;

        let describedBy;
        if (hasErrors) {
            describedBy = this.errorID;
        }

        return <label className={componentClasses}>
            <span id={this.labelID} className="inputBlock-labelAndDescription">
                <span className="inputBlock-labelText">
                    {this.props.label}
                </span>
                <Paragraph className='inputBlock-labelNote' content={this.props.labelNote}/>
            </span>

            <span className="inputBlock-inputWrap">
                <input
                    id={this.ID}
                    className={inputClasses}
                    type={this.type}
                    disabled={this.state.disabled}
                    required={this.props.required}
                    placeholder={this.props.placeholder}
                    aria-invalid={hasErrors}
                    aria-describedby={describedBy}
                    aria-labelledby={this.labelID}
                />
            </span>
            <ErrorMessages id={this.errorID} errors={this.state.errors}/>
        </label>;
    }
}
