/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import MeBoxMessage, { IMeBoxNotification, IMeBoxNotificationMessage, MeBoxMessageType } from "./MeBoxMessage";

export interface IVanillaHeaderNavProps {
    className?: string;
    data: Array<IMeBoxNotification | IMeBoxNotificationMessage>;
    emptyMessage?: string;
    type: MeBoxMessageType;
}

/**
 * Implements Navigation component for header
 */
export default class MeBoxMessageList extends React.Component<IVanillaHeaderNavProps> {
    public render() {
        const count = this.props.data.length;
        return (
            <div className={classNames("MeBoxMessageList", this.props.className)}>
                {count > 0 && (
                    <ul className="MeBoxMessageList-items">
                        {this.props.data.map((item, key) => {
                            return (
                                <MeBoxMessage {...item} type={this.props.type as any} key={`MeBoxMessageList-${key}`} />
                            );
                        })}
                    </ul>
                )}
                {count === 0 && <div className="frameBody-noContentMessage">{this.props.emptyMessage}</div>}
            </div>
        );
    }
}
