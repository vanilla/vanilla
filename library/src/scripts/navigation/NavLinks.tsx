/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { Component } from "react";
import Heading from "../layout/Heading";
import classNames from "classnames";
import SmartLink from "library/src/scripts/routing/links/SmartLink";
import { INavigationItem } from "../@types/api";
import { t } from "../dom/appUtils";
import ScreenReaderContent from "../utility/ScreenReaderContent";
import Translate from "../content/Translate";
import { navLinksClasses } from "navLinksStyles";

interface IProps {
    classNames?: string;
    title: string;
    items: INavigationItem[];
    url?: string;
    depth?: 1 | 2 | 3 | 4 | 5 | 6;
    accessibleViewAllMessage?: string;
}

/**
 * Component for displaying lists in "tiles"
 */
export default class NavLinks extends Component<IProps> {
    public render() {
        if (this.props.items.length !== 0) {
            const viewAll = t("View All");
            const classes = navLinksClasses();
            const contents = this.props.items.map((item, i) => {
                return (
                    <li className={classNames("navLinks-item", classes.item)} key={i}>
                        <SmartLink
                            to={item.url}
                            className={classNames("navLinks-link", classes.link)}
                            title={item.name}
                        >
                            {item.name}
                        </SmartLink>
                    </li>
                );
            });
            return (
                <article className={classNames("navLinks", this.props.classNames, classes.root)}>
                    <Heading
                        title={this.props.title}
                        className={classNames("navLinks-title", classes.title)}
                        depth={this.props.depth}
                    />
                    <ul className={classNames("navLinks-items", classes.items)}>
                        {contents}
                        {this.props.url &&
                            this.props.accessibleViewAllMessage && (
                                <li
                                    className={classNames("navLinks-item", "isViewAll", classes.item)}
                                    key={this.props.items.length}
                                >
                                    <SmartLink
                                        to={this.props.url}
                                        className={classNames("navLinks-viewAll", classes.viewAll)}
                                    >
                                        <span aria-hidden={true}>{viewAll}</span>
                                        <ScreenReaderContent>
                                            <Translate
                                                source={this.props.accessibleViewAllMessage}
                                                c0={this.props.title}
                                            />
                                        </ScreenReaderContent>
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
