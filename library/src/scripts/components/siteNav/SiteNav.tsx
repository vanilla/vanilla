/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { t } from "../../application";
import classNames from "classnames";
import SiteNavNode from "@library/components/siteNav/SiteNavNode";
import { getRequiredID } from "@library/componentIDs";

interface IProps {
    name: string;
    className?: string;
    children: any[];
}

export interface IState {
    id: string;
}

/**
 * Recursive component to generate site nav
 * No need to set "counter". It will be set automatically. Kept optional to not need to call it on the top level. Used for React's "key" values
 */
export default class SiteNav extends React.Component<IProps, IState> {
    public constructor(props) {
        super(props);
        this.state = {
            id: getRequiredID(props, "siteNav"),
        };
    }

    public get titleID() {
        return this.state.id + "-title";
    }

    public render() {
        const content =
            this.props.children && this.props.children.length > 0
                ? this.props.children.map((child, i) => {
                      return <SiteNavNode {...child} key={`navNode-${i}`} counter={i} titleID={this.titleID} />;
                  })
                : null;
        return (
            <nav onKeyDownCapture={this.handleKeyDown} className={classNames("siteNav", this.props.className)}>
                <h2 id={this.titleID} className="sr-only">{`${t("Category navigation from folder: ")}\"${
                    this.props.name
                }\"`}</h2>

                <ul className="siteNav-children" role="tree" aria-labelledby={this.titleID}>
                    {content}
                </ul>
            </nav>
        );
    }

    // https://www.w3.org/TR/wai-aria-practices-1.1/examples/treeview/treeview-1/treeview-1a.html
    private handleKeyDown = event => {
        const currentFocussedElement = document.activeElement;
        switch (`${event.controlKey ? "Control+" : ""}${event.shiftKey ? "Shift+" : ""}event.code`) {
            case "RightArrow":
                event.preventDefault();
                event.stopPropagation();
                /*
                    If a row is focused, and it is collapsed, expands the current row.
                    If a row is focused, and it is expanded, focuses the first cell in the row.
                    If a cell is focused, moves one cell to the right.
                    If focus is on the right most cell, focus does not move.
                 */
                break;
            case "LeftArrow":
                event.preventDefault();
                event.stopPropagation();
                break;
            /*
                    If a row is focused, and it is expanded, collapses the current row.
                    If a row is focused, and it is collapsed, moves to the parent row (if there is one).
                    If a cell in the first column is focused, focuses the row.
                    If a cell in a different column is focused, moves focus one cell to the left.
                 */
            case "DownArrow":
                event.preventDefault();
                event.stopPropagation();
                break;
            /*
                    Moves focus one row or one cell down, depending on whether a row or cell is currently focused.
                    If focus is on the bottom row, focus does not move.
                 */
            case "UpArrow":
                event.preventDefault();
                event.stopPropagation();
                break;
            /*
                    Moves focus one row or one cell up, depending on whether a row or cell is currently focused.
                    If focus is on the top row, focus does not move.
                 */
            case "Shift+Tab":
                event.preventDefault();
                event.stopPropagation();
                break;
            /*
                    Moves focus to the next interactive widget in the current row.
                    If there are no more interactive widgets in the current row, moves focus out of the treegrid.
                 */
            case "Home":
                event.preventDefault();
                event.stopPropagation();
                break;
            /*
                    If a cell is focused, moves focus to the previous interactive widget in the current row.
                    If a row is focused, moves focus out of the treegrid.
                 */
            case "End":
                event.preventDefault();
                event.stopPropagation();
                break;
            /*
                If a row is focused, moves to the first row.
                If a cell is focused, moves focus to the first cell in the row containing focus.
             */
            case "Control+Home":
                event.preventDefault();
                event.stopPropagation();
                break;
            /*
                If a row has focus, moves focus to the first row.
                If a cell has focus, moves focus to the cell in the first row in the same column as the cell that had focus.
             */
            case "Control+End":
                event.preventDefault();
                event.stopPropagation();
                break;
            /*
                If a row has focus, moves focus to the last row.
                If a cell has focus, moves focus to the cell in the last row in the same column as the cell that had focus.
             */
            case "Enter":
                event.preventDefault();
                event.stopPropagation();
                break;
            /*
                Performs default action associated with row or cell that has focus, e.g. opens message or navigate to link.
                If focus is on the cell with the expand/collapse button, and there is no other action, will toggle expansion of the current row.
            */
        }
    };
}
