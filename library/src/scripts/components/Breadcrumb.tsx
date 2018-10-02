/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import className from "classnames";
import { Link } from "react-router-dom";

export interface IBreadcrumbProps {
    className?: string;
    lastElement: boolean;
    url: string;
    name: string;
}

export default class Breadcrumb extends React.Component<IBreadcrumbProps> {
    public render() {
        let ariaCurrent;
        if (this.props.lastElement) {
            ariaCurrent = `page`;
        }

        return (
            <li className="breadcrumb">
                <Link
                    to={this.props.url}
                    title={this.props.name}
                    aria-current={ariaCurrent}
                    className={className("breadcrumb-link", this.props.className)}
                    itemScope
                    itemType="http://schema.org/Thing"
                    itemProp="item"
                >
                    <span className="breadcrumb-label" itemProp="name">
                        {this.props.name}
                    </span>
                </Link>
            </li>
        );
    }
}
