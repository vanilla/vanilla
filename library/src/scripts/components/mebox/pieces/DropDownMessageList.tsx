/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import DropDownMessage, { IDropDownMessage } from "./DropDownMesssage";

export interface IVanillaHeaderNavProps {
    className?: string;
    children: IDropDownMessage[];
    emptyMessage?: string;
}

/**
 * Implements Navigation component for header
 */
export default class DropDownMessageList extends React.Component<IVanillaHeaderNavProps> {
    public render() {
        const count = this.props.children.length;
        const content = this.props.children.map((item, key) => {
            return (
                <React.Fragment key={`dropDownMessageList-${key}`}>
                    <DropDownMessage {...item} />
                </React.Fragment>
            );
        });
        return (
            <div className={classNames("dropDownMessageList", this.props.className)}>
                {count > 0 && <ul className="dropDownMessageList-items">{content}</ul>}
                {count === 0 && <div className="frameBody-noContentMessage">{this.props.emptyMessage}</div>}
            </div>
        );
    }
}
