import React from 'react';
import classNames from 'classnames';
import {getUniqueID, IComponentID} from '@core/Interfaces/componentIDs';

interface IProps extends IComponentID{
    className?: string;
    hasError?: boolean;
    content: string | Node | null | undefined;
}

export default class Paragraph extends React.Component<IProps> {
    public ID: string;

    constructor(props) {
        super(props);
        this.ID = getUniqueID(props, 'Paragraph', true);
    }

    public render() {
        if (this.props.content) {
            const componentClasses = classNames(
                {'isError' : this.props.hasError},
                this.props.className
            );
            return <p id={this.props.ID} className={componentClasses}>{this.props.content}</p>;
        } else {
            return null;
        }
    }
}
