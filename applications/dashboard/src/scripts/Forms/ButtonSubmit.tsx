import { t } from '@core/application';
import React from 'react';
import classNames from 'classnames';
import * as PropTypes from "prop-types";
import Button from "./Button";
import {uniqueID, IComponentID} from '@core/Interfaces/componentIDs';

interface IProps extends IComponentID {
    content: string | Node;
    className?: string;
    disabled: boolean;
}

export default class ButtonSubmit extends React.Component<IProps> {
    public static defaultProps = {
        disabled: false,
    };
    public ID: string;

    constructor(props) {
        super(props);
        this.ID = uniqueID(props, 'submitButton');
    }

    public render() {
        const componentClasses = classNames(
            'Primary',
            'buttonCTA',
            'BigButton',
            'button-fullWidth',
            this.props.className
        );

        return <Button ID={this.ID} disabled={this.props.disabled} type='submit' content={this.props.content} className={componentClasses}>
            {this.props.content}
        </Button>;
    }
}
