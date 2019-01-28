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

interface IProps {
    classNames?: string;
    title: string;
    articles: INavigationItem[];
}

/**
 * Component for displaying lists in "tiles"
 */
export default class NavLinks extends Component<IProps> {
    public render() {
        if (this.props.articles.length !== 0) {
            const contents = this.props.articles.map(item => {
                return (
                    <li>
                        <SmartLink to={item.url} className="navLinks-link" title={item.name} />
                    </li>
                );
            });
            return (
                <section className={classNames("navLinks", this.props.classNames)}>
                    <Heading title={this.props.title} className="navLinks-title" />
                    <ul className="navLinks-links">{contents}</ul>
                </section>
            );
        } else {
            return null;
        }
    }
}
