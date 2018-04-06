import { t } from '@core/application';
import React from 'react';
import classNames from 'classnames';

interface IProps {
    parentID: string;
    className?: string;
    type?: string;
    labelID?: string;
    value?: string;
    descriptionID?: string;
    required?: boolean;
    errorMessage?: string;
    disabled?: boolean;
}

interface IState {
    disabled: boolean;
    valid?: boolean;
    value?: string;
}

export default class InputText extends React.Component<IProps, IState> {
    public type: string;

    constructor(props) {
        super(props);
        this.type = props.type || 'text';
        this.state = {
            value: props.value || '',
            disabled: props.disabled || false
        }
    }

    public render() {
        const componentClasses = classNames(
            'InputBox',
            'inputText',
            this.props.className
        );
        return <input type="{this.props.type}" className="{componentClasses}" disabled={this.state.disabled} value={this.state.value} aria-labelledby={this.props.labelID} aria-describedby={this.props.descriptionID} required={this.props.required} aria-invalid={!this.state.valid} />
    }
}
