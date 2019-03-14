/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { withRouter, RouteComponentProps } from "react-router-dom";
import SmartLink from "../../../routing/links/SmartLink";
import VanillaHeaderListItem from "library/src/scripts/headers/mebox/pieces/VanillaHeaderListItem";
import vanillaHeaderNavClasses from "../../VanillaHeaderNav";
import { getDynamicClassFromButtonType } from "../../../forms/Button";
import { ButtonTypes } from "@library/styles/buttonStyles";

export interface IHeaderNav {
    className?: string;
    to: string;
    children: React.ReactNode;
    linkClassName?: string;
    linkContentClassName?: string;
    buttonType?: ButtonTypes;
}

interface IProps extends IHeaderNav, RouteComponentProps<{}> {}

/**
 * Implements Navigation item component for header
 */
export class VanillaHeaderNavItem extends React.Component<IProps> {
    public render() {
        const isCurrent = this.currentPage();
        const classes = vanillaHeaderNavClasses();
        return (
            <VanillaHeaderListItem className={classNames(this.props.className, classes.root, { isCurrent })}>
                <SmartLink
                    to={this.props.to}
                    className={classNames(
                        this.props.linkClassName,
                        classes.link,
                        this.props.buttonType ? getDynamicClassFromButtonType(this.props.buttonType) : "",
                    )}
                >
                    <div
                        className={classNames(this.props.linkContentClassName, classes.linkContent, {
                            isCurrent,
                        })}
                    >
                        {this.props.children}
                    </div>
                </SmartLink>
            </VanillaHeaderListItem>
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
