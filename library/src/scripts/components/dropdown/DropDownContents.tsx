/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { flyoutPosition } from "@rich-editor/components/popovers/pieces/flyoutPosition";

export interface IProps {
    id: string;
    parentID: string;
    className?: string;
    children: React.ReactNode;
    isVisible?: boolean;
    renderAbove: boolean;
    renderLeft: boolean;
    onClick: (event: React.MouseEvent) => void;
}
/**
 * The contents of the dropdown (not the wrapper and not the button to toggle it).
 * Note that it renders an empty, hidden div when closed so that the aria-labelledby points to an element in the DOM.
 */
export default class DropDownContents extends React.Component<IProps> {
    public render() {
        if (this.props.isVisible) {
            return (
                <div
                    id={this.props.id}
                    aria-labelledby={this.props.parentID}
                    className={classNames("dropDown-contents", this.props.className)}
                    style={flyoutPosition(this.props.renderAbove, this.props.renderLeft)}
                    onClick={this.props.onClick}
                >
                    {this.props.children}
                </div>
            );
        } else {
            return (
                <div id={this.props.id} aria-hidden={true} aria-labelledby={this.props.parentID} className="sr-only" />
            ); // for accessibility
        }
    }
}
