/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import Heading from "@library/components/Heading";
import SmartLink from "@library/components/navigation/SmartLink";
import { Permission } from "@library/users/Permission";
import { connect } from "react-redux";
import UsersModel from "@library/users/UsersModel";
import { UserDropDown } from "@library/components/mebox/pieces/UserDropdown";

export interface ILink {
    text: string;
    to: string;
    count?: number;
    className?: string;
    permission?: string;
}

export interface ILinkGroup {
    title?: string;
    data: ILink[];
    permission?: string;
    className?: string;
    linkClass?: string;
}

export class LinkGroup extends React.Component<ILinkGroup> {
    public render() {
        let hasVisibleChildren = false;
        const content = this.props.data.map((link, index) => {
            const { text, to, permission, count, className } = link;

            if (!hasVisibleChildren && permission) {
                hasVisibleChildren = true;
            }
            if (permission) {
                return (
                    <li className="linkGroup-item" key={`linkGroup-${index}`}>
                        {this.props.title && <Heading title={this.props.title} className="linkGroup-title" />}
                        <SmartLink to={to} className={classNames("linkGroup-link", className)}>
                            <span className="linkGroup-text">{text}</span>
                            {count && <span className="linkGroup-count">{count}</span>}
                        </SmartLink>
                    </li>
                );
            } else {
                return null;
            }
        });
        const linkGroup = (
            <div className={classNames("linkGroup", this.props.className)}>
                <ul className="linkGroup-content">{content}</ul>
            </div>
        );
        if (typeof this.props.permission === "string") {
            <Permission permission={this.props.permission}>{linkGroup}</Permission>;
        } else {
            {
                linkGroup;
            }
        }
    }
}

const withRedux = connect(UsersModel.mapStateToProps);
export default withRedux(LinkGroup);
