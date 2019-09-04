/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import SmartLink from "@library/routing/links/SmartLink";
import classNames from "classnames";
import { breadcrumbsClasses } from "@library/navigation/breadcrumbsStyles";

interface IProps {
    className?: string;
    lastElement: boolean;
    url: string;
    name: string;
}

/**
 * A component representing a single crumb in a breadcrumb component.
 */
export default class Breadcrumb extends React.Component<IProps> {
    public render() {
        const classes = breadcrumbsClasses();
        let ariaCurrent;
        if (this.props.lastElement) {
            ariaCurrent = `page`;
        }

        return (
            <li className={classes.breadcrumb}>
                <SmartLink
                    to={this.props.url}
                    title={this.props.name}
                    aria-current={ariaCurrent}
                    className={classNames(classes.link, this.props.className, { isCurrent: ariaCurrent })}
                    itemScope
                    itemType="http://schema.org/Thing"
                    itemProp="item"
                >
                    <span itemProp="name">{this.props.name}</span>
                </SmartLink>
            </li>
        );
    }
}
