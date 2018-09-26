/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { t } from "@library/application";

export interface IProps {
    id: string;
    parentID: string;
    className?: string;
    children: React.ReactNode;
    isVisible?: boolean;
    isPositionedFromTop: boolean;
    isPositionedFromRight: boolean;
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
                    style={{
                        top: this.props.isPositionedFromTop ? "100%" : undefined,
                        right: this.props.isPositionedFromRight ? "0" : undefined,
                        bottom: !this.props.isPositionedFromTop ? "100%" : undefined,
                        left: !this.props.isPositionedFromRight ? "0" : undefined,
                    }}
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
