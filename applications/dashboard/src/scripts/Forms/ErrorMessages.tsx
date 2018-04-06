import React from 'react';
import classNames from 'classnames';
import UniqueID from "react-html-id";

interface IProps {
    id?: string;
    className?: string;
    errors?: string[];
}

export default class ErrorMessages extends React.Component<IProps> {
    public render() {
        const errors = this.props.errors;
        if (errors && errors.length > 0) {
            const componentClasses = classNames(
                'inputBlock-errors',
                this.props.className
            );
            const errorList = errors.map((error, index) => {
                return <span key={ index } className="inputBlock-error">{error}</span>;
            });

            return <span id={this.props.id} className={componentClasses}>
                {errorList}
            </span>;
        } else {
            return null;
        }
    }
}
