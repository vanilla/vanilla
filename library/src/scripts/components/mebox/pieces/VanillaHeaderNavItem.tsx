/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import InternalOrExternalLink from "@library/components/InternalOrExternalLink";
import { withRouter, RouteComponentProps } from "react-router-dom";

export interface IVanillaHeaderNavItemProps {
    className?: string;
    to: string;
    name: string;
}

interface IProps extends IVanillaHeaderNavItemProps, RouteComponentProps<{}> {}

/**
 * Implements Navigation component for header
 */
export class VanillaHeaderNavItem extends React.Component<IProps> {
    public render() {
        return (
            <li
                className={classNames("vanillaHeaderNav-item", this.props.className, { isCurrent: this.currentPage() })}
            >
                <InternalOrExternalLink
                    to={this.props.to}
                    className={classNames("vanillaHeaderNav-link", { isCurrent: this.currentPage() })}
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
            return this.props.location.pathname === this.props.to;
        } else {
            return false;
        }
    };
}

export default withRouter<IProps>(VanillaHeaderNavItem);
