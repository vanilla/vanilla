import { t } from '@core/application';
import React from 'react';
import classNames from 'classnames';
import UniqueID from "react-html-id";
import ErrorMessages from "./ErrorMessages";
import Paragraph from "./Paragraph";

interface IBaseProps {
    parentID: string;
    className?: string;
    label: string;
    labelNote?: string;
    inputClassNames?: string;
    type?: string;
    labelID?: string;
    value?: string;
    valid?: boolean;
    descriptionID?: string;
    required?: boolean;
    errors?: string;
    disabled?: boolean;
}

interface IFirst extends IBaseProps {
    parentID: string;
}

interface ISecond extends IBaseProps {
    ID: string;
}

type IProps = IFirst | ISecond;

interface IState {
    disabled: boolean;
    valid?: boolean;
    value?: string;
    errors?: string[];
}

export default class InputTextBlock extends React.Component<IProps, IState> {
    public ID: string;
    public errorID: string;
    public labelID: string;
    public nextUniqueId: () => string;
    public type: string;

    constructor(props) {
        super(props);

        if (props.ID && props.parentID) {
            throw new Error(`You're not allowed to have both a parentID and an ID.`);
        }

        if (props.parentID) {
            UniqueID.enableUniqueIds(this);
            this.ID = props.parentID + '-button' + this.nextUniqueId();
        } else {
            this.ID = props.ID;
        }

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
                    aria-invalid={hasErrors}
                    aria-describedby={describedBy}
                    aria-labelledby={this.labelID}
                />
            </span>
            <ErrorMessages id={this.errorID} errors={this.state.errors}/>
        </label>;
    }
}
