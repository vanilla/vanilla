/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import { LiveMessage } from "react-aria-live";
import { accessibleErrorClasses } from "@library/forms/formElementStyles";
import { userContentClasses } from "@library/content/userContentStyles";

interface IProps {
    id: string;
    className?: string;
    paragraphClassName?: string;
    wrapClassName?: string;
    error: string;
    ariaHidden?: boolean; // Optionally hide visual message. There will still be a message sent through LiveMessage
}

export default class AccessibleError extends React.PureComponent<IProps> {
    public static defaultProps = {
        renderAsBlock: true,
    };

    public render() {
        const { error, id } = this.props;
        const classes = accessibleErrorClasses();
        const classesUserContent = userContentClasses();
        return (
            <>
                <LiveMessage clearOnUnmount={true} message={error} aria-live="assertive" />
                <span
                    id={id}
                    className={classNames(this.props.className, classes.root)}
                    aria-hidden={this.props.ariaHidden}
                >
                    <span className={this.props.wrapClassName} aria-hidden={this.props.ariaHidden}>
                        <p className={classNames(classes.paragraph, this.props.paragraphClassName)}>{error}</p>
                    </span>
                </span>
            </>
        );
    }
}
