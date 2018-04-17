import React from 'react';
import classNames from 'classnames';
import {uniqueID, IComponentID} from '@core/Interfaces/componentIDs';

interface IProps extends IComponentID {
    className?: string;
    isError?: boolean;
    content?: string | Node | null;
}

export default class Paragraph extends React.Component<IProps> {
    public id: string;

    constructor(props) {
        super(props);
        this.id = uniqueID(props, 'Paragraph', true);
    }

    public render() {
        if (this.props.content) {
            const componentClasses = classNames(
                {'isError' : this.props.isError},
                this.props.className
            );

            let accessibilityProps = {};

            if (this.props.isError) {
                accessibilityProps = {
                    'aria-live': 'assertive',
                    'role': 'alert',
                };
            }

            return <p id={this.props.id} className={componentClasses} {...accessibilityProps}>{this.props.content}</p>;
        } else {
            return null;
        }
    }
}
