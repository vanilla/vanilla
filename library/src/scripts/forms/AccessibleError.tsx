/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import { LiveMessage } from "react-aria-live";
import { AccessibleErrorClasses } from "@library/forms/formElementStyles";

interface IProps {
    id: string;
    className?: string;
    error: string;
    renderAsBlock: boolean;
}

export default class AccessibleError extends React.PureComponent<IProps> {
    public render() {
        const { error, renderAsBlock, id } = this.props;
        const classes = AccessibleErrorClasses();
        const Tag = renderAsBlock ? `div` : `span`;
        return (
            <>
                <LiveMessage clearOnUnmount={true} message={error} aria-live="assertive" />
                <Tag id={id} className={classNames(this.props.className, classes.root)}>
                    {error}
                </Tag>
            </>
        );
    }
}
