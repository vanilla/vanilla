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

interface IState {
    id: string;
}

export default class Button extends React.Component<IProps, IState> {
    public static defaultProps = {
        disabled: false,
        type: 'button'
    };

    constructor(props) {
        super(props);
        this.state = {
            id: uniqueID(props, "button"),
        };
    }


    public render() {
        const componentClasses = classNames(
            'button',
            'Button',
            this.props.className
        );

        return <button id={this.state.id} disabled={this.props.disabled} type={this.props.type} className={componentClasses}>
            {this.props.content}
        </button>;
    }
}
