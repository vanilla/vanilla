/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import MeBoxDropDownItem, {
    IMeBoxMessageItem,
    IMeBoxNotificationItem,
    MeBoxItemType,
} from "@library/headers/mebox/pieces/MeBoxDropDownItem";
import { frameBodyClasses } from "@library/layout/frame/frameStyles";
import classNames from "classnames";

export interface IVanillaHeaderNavProps {
    className?: string;
    data: Array<IMeBoxMessageItem | IMeBoxNotificationItem>;
    emptyMessage?: string;
    type: MeBoxItemType;
}

/**
 * Implements Navigation component for header
 */
export default class MeBoxDropDownItemList extends React.Component<IVanillaHeaderNavProps> {
    public render() {
        const count = this.props.data.length;
        const classesFrameBody = frameBodyClasses();
        return (
            <div className={classNames("meBoxMessageList", this.props.className)}>
                {count > 0 && (
                    <ul className="meBoxMessageList-items">
                        {this.props.data.map((item, key) => {
                            return (
                                <MeBoxDropDownItem {...item} key={`MeBoxDropDownItemList-${this.props.type}-${key}`} />
                            );
                        })}
                    </ul>
                )}
                {count === 0 && (
                    <div className={classNames("frameBody-noContentMessage", classesFrameBody.noContentMessage)}>
                        {this.props.emptyMessage}
                    </div>
                )}
            </div>
        );
    }
}
