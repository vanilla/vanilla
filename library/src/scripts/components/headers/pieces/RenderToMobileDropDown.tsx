/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import ReactDOM from "react-dom";

interface IProps {
    contentRef: React.RefObject<HTMLDivElement>;
    children: React.ReactNode;
    className?: string;
}

/**
 * Helper component to render to the HeaderDropDown from anywhere.
 */
export default class RenderToMobileDropDown extends React.Component<IProps> {
    public render() {
        return this.props.contentRef.current && this.props.children ? (
            <React.Fragment>{ReactDOM.createPortal(this.props.children, this.props.contentRef.current)}</React.Fragment>
        ) : null;
    }
}
