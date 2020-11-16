/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { flyoutPosition } from "@rich-editor/flyouts/pieces/flyoutPosition";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import { TabHandler } from "@vanilla/dom-utils";

export interface IProps {
    id: string;
    className?: string;
    children: React.ReactNode;
    isVisible?: boolean;
    renderAbove: boolean;
    renderLeft: boolean;
    renderCenter: boolean;
    legacyMode?: boolean;
    openAsModal?: boolean;
    selfPadded?: boolean;
    size: DropDownContentSize;
    horizontalOffset?: boolean;
    contentRef?: React.RefObject<HTMLDivElement>;
}

export enum DropDownContentSize {
    SMALL = "small",
    MEDIUM = "medium",
}

/**
 * The contents of the flyouts (not the wrapper and not the button to toggle it).
 * Note that it renders an empty, hidden div when closed so that the aria-labelledby points to an element in the DOM.
 */
export default class DropDownContents extends React.Component<IProps> {
    public render() {
        const classes = dropDownClasses();
        const asDropDownClasses = !this.props.openAsModal
            ? classNames("dropDown-contents", classes.contents, {
                  isMedium: this.props.size === DropDownContentSize.MEDIUM,
              })
            : undefined;
        const asModalClasses = this.props.openAsModal ? classNames("dropDown-asModal", classes.asModal) : undefined;
        if (this.props.openAsModal) {
            return this.props.children;
        }

        return (
            <div
                ref={this.props.contentRef}
                id={this.props.id}
                className={classNames(asDropDownClasses, asModalClasses, this.props.className, {
                    [classes.verticalPadding]: !this.props.selfPadded,
                    [classes.contentOffsetCenter]: this.props.renderCenter,
                    [classes.contentOffsetLeft]: this.props.horizontalOffset && this.props.renderLeft,
                    [classes.contentOffsetRight]: this.props.horizontalOffset && !this.props.renderLeft,
                })}
                style={flyoutPosition(
                    this.props.renderAbove,
                    this.props.renderLeft,
                    !!this.props.legacyMode,
                    this.props.renderCenter,
                )}
                onClick={this.doNothing}
                tabIndex={-1}
                onMouseDown={this.forceTryFocus}
            >
                {this.props.children}
            </div>
        );
    }

    /**
     * Our focus watcher has an exclusion for moving away focus when focus is moved to the body.
     * This is standard behaviour on mousedown, if a non-focusable element is clicked.
     *
     * Unfortunately if this is rendered inside of a `content-editable`,
     * the content editable will be focused instead of the body. This simple handler ensures that focus goes to the body
     * if a non-focusable element is clicked inside a dropdown inside a content-editable.
     */
    private forceTryFocus = (e: React.MouseEvent) => {
        if (e.target instanceof HTMLElement) {
            if (!TabHandler.isTabbable(e.target)) {
                // this.doNothing(e);
                // this.selfRef.current && this.selfRef.current.focus();
            }
        }
    };

    private doNothing = (e: React.MouseEvent) => {
        e.stopPropagation();
        e.nativeEvent.stopPropagation();
        e.nativeEvent.stopImmediatePropagation();
    };
}
