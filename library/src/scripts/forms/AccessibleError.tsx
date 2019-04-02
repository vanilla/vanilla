/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import { LiveMessage } from "react-aria-live";
import { AccessibleErrorClasses } from "@library/forms/formElementStyles";
import { t } from "@library/utility/appUtils";
import { userContentClasses } from "@library/content/userContentStyles";

interface IProps {
    id: string;
    className?: string;
    error: string;
    ariaHidden?: boolean;
}

export default class AccessibleError extends React.PureComponent<IProps> {
    public static defaultProps = {
        renderAsBlock: true,
    };

    public render() {
        const { error, id } = this.props;
        const classes = AccessibleErrorClasses();
        const classesUserContent = userContentClasses();
        return (
            <>
                <LiveMessage clearOnUnmount={true} message={error} aria-live="assertive" />
                <span
                    id={id}
                    className={classNames(this.props.className, classes.root)}
                    aria-hidden={this.props.ariaHidden}
                >
                    <span className={classesUserContent.root}>
                        <p className={classes.paragraph}>{error}</p>
                    </span>
                </span>
            </>
        );
    }
}
