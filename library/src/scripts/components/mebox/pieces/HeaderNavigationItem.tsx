/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import InternalOrExternalLink from "@library/components/InternalOrExternalLink";

export interface IHeaderNavigationItemProps {
    className?: string;
    location: any;
    url: string;
    name: string;
}

/**
 * Implements Navigation component for header
 */
export default class HeaderNavigationItem extends React.Component<IHeaderNavigationItemProps> {
    public render() {
        return (
            <li
                className={classNames("headerNavigation-item", this.props.className, { isCurrent: this.currentPage() })}
            >
                <InternalOrExternalLink
                    to={this.props.url}
                    className={classNames("headerNavigation-link", { isCurrent: this.currentPage() })}
                >
                    {this.props.name}
                </InternalOrExternalLink>
            </li>
        );
    }

    /**
     * Checks if we're on the current page
     * Note that this won't work with non-canonical URLs
     */
    public currentPage = (): boolean => {
        if (this.props.location && this.props.location.pathname) {
            return this.props.location.pathname === this.props.url;
        } else {
            return false;
        }
    };
}
