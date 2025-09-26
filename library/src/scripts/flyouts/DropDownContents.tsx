/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { flyoutPosition } from "@library/editor/flyouts/pieces/flyoutPosition";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import { TabHandler } from "@vanilla/dom-utils";
import { PageBoxDepthContextProvider } from "@library/layout/PageBox.context";
import { StackingContextProvider } from "@vanilla/react-utils";
import { cx } from "@emotion/css";

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
export default function DropDownContents(props: IProps) {
    const classes = dropDownClasses.useAsHook();
    const asDropDownClasses = !props.openAsModal
        ? classNames("dropDown-contents", classes.contents, {
              isMedium: props.size === DropDownContentSize.MEDIUM,
          })
        : undefined;
    const asModalClasses = props.openAsModal ? classNames("dropDown-asModal", classes.asModal) : undefined;

    if (props.openAsModal) {
        return <>{props.children}</>;
    }

    return (
        <StackingContextProvider>
            <PageBoxDepthContextProvider depth={3}>
                <div
                    ref={props.contentRef}
                    id={props.id}
                    className={cx(
                        asDropDownClasses,
                        asModalClasses,
                        {
                            [classes.verticalPadding]: !props.selfPadded,
                            [classes.contentOffsetCenter]: props.renderCenter,
                            [classes.contentOffsetLeft]: props.horizontalOffset && props.renderLeft,
                            [classes.contentOffsetRight]: props.horizontalOffset && !props.renderLeft,
                        },
                        props.className,
                    )}
                    style={flyoutPosition(props.renderAbove, props.renderLeft, !!props.legacyMode, props.renderCenter)}
                    onClick={(e) => {
                        e.stopPropagation();
                        e.nativeEvent.stopPropagation();
                        e.nativeEvent.stopImmediatePropagation();
                    }}
                    tabIndex={-1}
                    onMouseDown={
                        /**
                         * Our focus watcher has an exclusion for moving away focus when focus is moved to the body.
                         * This is standard behaviour on mousedown, if a non-focusable element is clicked.
                         *
                         * Unfortunately if this is rendered inside of a `content-editable`,
                         * the content editable will be focused instead of the body. This simple handler ensures that focus goes to the body
                         * if a non-focusable element is clicked inside a dropdown inside a content-editable.
                         */
                        (e: React.MouseEvent) => {
                            if (e.target instanceof HTMLElement) {
                                if (!TabHandler.isTabbable(e.target)) {
                                    // this.doNothing(e);
                                    // this.selfRef.current && this.selfRef.current.focus();
                                }
                            }
                        }
                    }
                >
                    {props.children}
                </div>
            </PageBoxDepthContextProvider>
        </StackingContextProvider>
    );
}
