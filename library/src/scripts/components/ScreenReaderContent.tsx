/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";

interface IProps {
    children: React.ReactNode;
    tag?: string;
}

/**
 * Children of component only visible to screen readers
 */
export default class ScreenReaderContent extends React.PureComponent<IProps> {
    public render() {
        const Tag = this.props.tag ? `${this.props.tag}` : "div";
        return <Tag className="sr-only">{this.props.children}</Tag>;
    }
}
