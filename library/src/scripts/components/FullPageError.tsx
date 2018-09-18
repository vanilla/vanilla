/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";

interface IProps {
    children: React.ReactNode;
}

/**
 * An accessible full page error message.
 */
export default class FullPageError extends React.Component<IProps> {
    public render() {
        return (
            <div className="fullPageError" aria-role="alert">
                {this.props.children}
            </div>
        );
    }
}
