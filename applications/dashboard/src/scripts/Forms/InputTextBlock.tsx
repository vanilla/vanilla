import { t } from '@core/application';
import React from 'react';
import classNames from 'classnames';
import ErrorMessages from "./ErrorMessages";
import Paragraph from "./Paragraph";
import {uniqueID, IComponentID} from '@core/Interfaces/componentIDs';

export interface IInputTextProps extends IComponentID{
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
    required?: boolean;
    errors?: string[];
    disabled?: boolean;
    onChange?: any;
}

interface IState {
    ID: string;
}


export default class InputTextBlock extends React.Component<IInputTextProps, IState> {
    public static defaultProps = {
        value: '',
        disabled: false,
        type: 'text',
        errors: [],
    };

    constructor(props) {
        super(props);
        this.state = {
            ID: uniqueID(props, "inputText"),
        };
    }

    get labelID():string {
        return this.state.ID + "-label";
    }

    get errorID():string {
        return this.state.ID + "-errors";
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

        const hasErrors = this.props.errors && this.props.errors.length > 0;

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
                    id={this.state.ID}
                    className={inputClasses}
                    value={this.props.value}
                    type={this.props.type}
                    disabled={this.props.disabled}
                    required={this.props.required}
                    placeholder={this.props.placeholder}
                    aria-invalid={hasErrors}
                    aria-describedby={describedBy}
                    aria-labelledby={this.labelID}
                    onChange={this.props.onChange}
                />
            </span>
            <ErrorMessages id={this.errorID} errors={this.props.errors}/>
        </label>;
    }
}
