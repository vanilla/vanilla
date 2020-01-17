/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { ButtonTypes } from "@library/forms/buttonStyles";
import titleBarNavClasses from "@library/headers/titleBarNavStyles";
import SmartLink from "@library/routing/links/SmartLink";
import { getButtonStyleFromBaseClass } from "@library/forms/Button";
import { RouteComponentProps, withRouter } from "react-router";
import classNames from "classnames";
import TitleBarListItem from "@library/headers/mebox/pieces/TitleBarListItem";
import { formatUrl, siteUrl } from "@library/utility/appUtils";

export interface ITitleBarNav {
    className?: string;
    to: string;
    children: React.ReactNode;
    linkClassName?: string;
    linkContentClassName?: string;
    buttonType?: ButtonTypes;
    permission?: string;
}

interface IProps extends ITitleBarNav, RouteComponentProps<{}> {}

/**
 * Implements Navigation item component for header
 */
export class TitleBarNavItem extends React.Component<IProps> {
    public render() {
        const isCurrent = this.currentPage();
        const classes = titleBarNavClasses();
        return (
            <TitleBarListItem className={classNames(this.props.className, classes.root, { isCurrent })}>
                <SmartLink
                    to={this.props.to}
                    className={classNames(
                        this.props.linkClassName,
                        classes.link,
                        this.props.buttonType ? getButtonStyleFromBaseClass(this.props.buttonType) : "",
                    )}
                >
                    <div
                        className={classNames(
                            this.props.linkContentClassName,
                            classes.linkContent,
                            isCurrent ? classes.linkActive : "",
                        )}
                    >
                        {this.props.children}
                    </div>
                </SmartLink>
            </TitleBarListItem>
        );
    }

    /**
     * Checks if we're on the current page
     * Note that this won't work with non-canonical URLHeaderLogo.tsxs
     */
    public currentPage = (): boolean => {
        if (this.props.location && this.props.location.pathname) {
            return siteUrl(window.location.pathname) === formatUrl(this.props.to, true);
        } else {
            return false;
        }
    };
}

export default withRouter(TitleBarNavItem);
