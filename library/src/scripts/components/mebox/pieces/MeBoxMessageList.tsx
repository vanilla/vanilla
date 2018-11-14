/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import MeBoxMessage, { IMeBoxMessage } from "./MeBoxMessage";

export interface IVanillaHeaderNavProps {
    className?: string;
    children: IMeBoxMessage[];
    emptyMessage?: string;
}

/**
 * Implements Navigation component for header
 */
export default class MeBoxMessageList extends React.Component<IVanillaHeaderNavProps> {
    public render() {
        const count = this.props.children.length;
        const content = this.props.children.map((item, key) => {
            return <MeBoxMessage {...item} key={`MeBoxMessageList-${key}`} />;
        });
        return (
            <div className={classNames("MeBoxMessageList", this.props.className)}>
                {count > 0 && <ul className="MeBoxMessageList-items">{content}</ul>}
                {count === 0 && <div className="frameBody-noContentMessage">{this.props.emptyMessage}</div>}
            </div>
        );
    }
}
