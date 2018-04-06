import { t } from '@core/application';
import React from 'react';
import classNames from 'classnames';
import * as PropTypes from "prop-types";
import UniqueID from "react-html-id";

interface IProps {
    ID: string;
    className?: string;
    type: string;
    content: string | Node;
    disabled: boolean;
}

interface IState {
    disabled: boolean;
}

export default class Button extends React.Component<IProps, IState> {
    public ID: string;
    public type: string;
    public nextUniqueId: () => string;

    constructor(props) {
        super(props);

        this.ID = props.ID;
        this.type = props.type || 'button';
        this.state = {
            disabled: props.disabled,
        };
    }


    public render() {
        const componentClasses = classNames(
            'button',
            'Button',
            this.props.className
        );

        return <button id={this.ID} disabled={this.state.disabled} type={this.type} className={componentClasses}>
            {this.props.content}
        </button>;
    }
}
