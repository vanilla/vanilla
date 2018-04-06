import { t } from '@core/application';
import React from 'react';
import classNames from 'classnames';
import * as PropTypes from "prop-types";
import Button from "./Button";
import UniqueID from "react-html-id";

interface IProps {
    content: string | Node;
    parentID: string;
    className: string;
}

interface IState {
    disabled: boolean;
}

export default class ButtonSubmit extends React.Component<IProps, IState> {
    public ID: string;
    public type: string;
    public nextUniqueId: () => string;

    constructor(props) {
        super(props);
        this.type = "submit";
        UniqueID.enableUniqueIds(this);
        this.ID = props.parentID + "-submitButton" + this.nextUniqueId();
        this.state = {
            disabled: props.disabled,
        };
    }

    public render() {
        const componentClasses = classNames(
            'Primary',
            'buttonCTA',
            'BigButton',
            'button-fullWidth',
            this.props.className
        );

        return <Button ID={this.ID} disabled={this.state.disabled} type="submit" content={this.props.content} className={componentClasses}>
            {this.props.content}
        </Button>;
    }
}
