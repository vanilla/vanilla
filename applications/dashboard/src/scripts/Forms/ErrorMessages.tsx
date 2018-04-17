import React from 'react';
import classNames from 'classnames';
import {uniqueID, IComponentID} from '@core/Interfaces/componentIDs';

interface IProps {
    id?: string;
    className?: string;
    errors?: any[];
}

export default class ErrorMessages extends React.Component<IProps> {
    public id: string;

    constructor(props) {
        super(props);
        this.id = uniqueID(props, 'errorMessages', true);
    }

    public render() {
        const errors = this.props.errors;
        if (errors && errors.length > 0) {
            const componentClasses = classNames(
                'inputBlock-errors',
                this.props.className
            );
            const errorList = errors.map((error:any, index) => {
                return <span key={ index } className="inputBlock-error">{error.message}</span>;
            });

            return <span id={this.id} className={componentClasses}>
                {errorList}
            </span>;
        } else {
            return null;
        }
    }
}
