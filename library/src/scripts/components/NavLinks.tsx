/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { Component } from "react";
import Heading from "@library/components/Heading";
import classNames from "classnames";
import SmartLink from "@library/components/navigation/SmartLink";
import { INavigationItem } from "@library/@types/api";
import { t } from "@library/application";

interface IProps {
    classNames?: string;
    title: string;
    items: INavigationItem[];
    url?: string;
    depth?: 1 | 2 | 3 | 4 | 5 | 6;
}

/**
 * Component for displaying lists in "tiles"
 */
export default class NavLinks extends Component<IProps> {
    public render() {
        if (this.props.items.length !== 0) {
            const viewAll = t("View All");
            const contents = this.props.items.map((item, i) => {
                return (
                    <li className="navLinks-item" key={i}>
                        <SmartLink to={item.url} className="navLinks-link" title={item.name}>
                            {item.name}
                        </SmartLink>
                    </li>
                );
            });
            return (
                <article className={classNames("navLinks", this.props.classNames)}>
                    <Heading title={this.props.title} className="navLinks-title" depth={this.props.depth} />
                    <ul className="navLinks-items">
                        {contents}
                        {this.props.url && (
                            <li className="navLinks-item" key={this.props.items.length}>
                                <SmartLink to={this.props.url} className="navLinks-viewAll">
                                    {viewAll}
                                </SmartLink>
                            </li>
                        )}
                    </ul>
                </article>
            );
        } else {
            return null;
        }
    }
}
