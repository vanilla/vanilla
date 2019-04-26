/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import ThemeLogo, { LogoType } from "@library/theming/ThemeLogo";
import { formatUrl, t } from "@library/utility/appUtils";
import SmartLink from "@library/routing/links/SmartLink";
import { titleBarLogoClasses } from "@library/headers/titleBarStyles";
import classNames from "classnames";

export interface IHeaderLogo {
    className?: string;
    to: string;
    logoClassName?: string;
    logoType: LogoType;
    color?: string;
}

/**
 * Implements Logo component
 */
export default class HeaderLogo extends React.Component<IHeaderLogo> {
    public static defaultProps: Partial<IHeaderLogo> = {
        to: formatUrl("/"),
    };

    public render() {
        const classes = titleBarLogoClasses();
        const logoClassName = classNames("headerLogo-logo", this.props.logoClassName, classes.logo);

        return (
            <SmartLink to={this.props.to} className={classNames("headerLogo", this.props.className)}>
                <span className={classNames("headerLogo-logoFrame", classes.logoFrame)}>
                    <ThemeLogo alt={t("Vanilla")} className={logoClassName} type={this.props.logoType} />
                </span>
            </SmartLink>
        );
    }
}
