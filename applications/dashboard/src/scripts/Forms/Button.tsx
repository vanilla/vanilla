import { t } from '@core/application';
import React from 'react';
import classNames from 'classnames';
import { uniqueID, IComponentID } from '@core/Interfaces/componentIDs';

interface IProps extends IComponentID {
    className?: string;
    type: string;
    content: string | Node;
    disabled: boolean;
}

export default class Button extends React.Component<IProps> {
    public static defaultProps = {
        disabled: false,
    };
    public ID: string;
    public type: string;

    constructor(props) {
        super(props);
        this.ID = uniqueID(props, 'button');
        this.type = props.type || 'button';
    }


    public render() {
        const componentClasses = classNames(
            'button',
            'Button',
            this.props.className
        );

        return <button id={this.ID} disabled={this.props.disabled} type={this.type} className={componentClasses}>
            {this.props.content}
        </button>;
    }
}
