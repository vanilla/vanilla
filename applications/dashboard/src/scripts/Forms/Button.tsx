import { t } from '@core/application';
import React from 'react';
import classNames from 'classnames';
import * as PropTypes from "prop-types";
import UniqueID from "react-html-id";

interface IBaseProps {
    className: string;
    type: string;
    content: string | Node;
    disabled: boolean;
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
}

export default class Button extends React.Component<IProps, IState> {
    public ID: string;
    public type: string;
    public nextUniqueId: () => string;

    constructor(props) {
        super(props);

        if (props.ID && props.parentID) {
            throw new Error("You're not allowed to have both a parentID and an ID.");
        }

        if (props.parentID) {
            UniqueID.enableUniqueIds(this);
            this.ID = props.parentID + "-button" + this.nextUniqueId();
        } else {
            this.ID = props.ID;
        }

        this.type = props.type || "button";
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
