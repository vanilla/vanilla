import React from 'react';
import classNames from 'classnames';

interface IProps {
    ID?: string;
    className?: string;
    hasError?: boolean;
    content: string | Node | null | undefined;
}

export default class Paragraph extends React.Component<IProps> {
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
