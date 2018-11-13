/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { withRouter, RouteComponentProps } from "react-router-dom";
import SmartLink from "@library/components/navigation/SmartLink";

export interface IVanillaHeaderNavItemProps {
    className?: string;
    to: string;
    name: string;
}

interface IProps extends IVanillaHeaderNavItemProps, RouteComponentProps<{}> {}

/**
 * Implements Navigation item component for header
 */
export class VanillaHeaderNavItem extends React.Component<IProps> {
    public render() {
        return (
            <li
                className={classNames("vanillaHeaderNav-item", this.props.className, { isCurrent: this.currentPage() })}
            >
                <SmartLink
                    to={this.props.to}
                    className={classNames("vanillaHeaderNav-link", { isCurrent: this.currentPage() })}
                >
                    {this.props.name}
                </SmartLink>
            </li>
        );
    }

    /**
     * Checks if we're on the current page
     * Note that this won't work with non-canonical URLHeaderLogo.tsxs
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
