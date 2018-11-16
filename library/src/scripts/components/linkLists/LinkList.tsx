/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import LinkGroup, { ILinkGroup } from "@library/components/linkLists/LinkGroup";
import { IInjectableUserState } from "@library/users/UsersModel";

export interface ILinkList extends IInjectableUserState {
    className?: string;
    data: ILinkGroup[];
    groupClass?: string;
    linkClass?: string;
}

export default class LinkList extends React.Component<ILinkList> {
    public render() {
        let hasVisibleChildren = false;
        const content = this.props.data.map((group, index) => {
            hasVisibleChildren = true;
            return (
                <li className="linkList-group">
                    <LinkGroup
                        {...group}
                        className={classNames(group.className, this.props.groupClass)}
                        linkClass={this.props.linkClass}
                        key={`linkGroup-${index}`}
                    />
                </li>
            );
        });
        return hasVisibleChildren ? (
            <div className={classNames("linkList", this.props.className)}>
                <ul className="linkList-content">{content}</ul>
            </div>
        ) : null;
    }
}
