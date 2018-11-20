/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
interface IProps {
    if: boolean;
    children: React.ReactNode;
}

/**
 * Clean up conditional renders with this component
 */
export default class Condition extends React.PureComponent<IProps> {
    public render() {
        return this.props.if ? this.props.children : null;
    }
}
