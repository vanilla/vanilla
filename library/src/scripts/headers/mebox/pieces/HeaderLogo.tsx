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
import { titleBarVariables } from "@library/headers/TitleBar.variables";
import classNames from "classnames";
import { navigationVariables } from "@library/headers/navigationVariables";

export interface IHeaderLogo {
    className?: string;
    logoClassName?: string;
    logoType: LogoType;
    color?: string;
    overwriteLogo?: string; // for storybook
}

/**
 * Implements Logo component
 */
export default class HeaderLogo extends React.Component<IHeaderLogo> {
    public render() {
        const { doubleLogoStrategy } = titleBarVariables().logo;
        const classes = titleBarLogoClasses();
        const desktopOrMobileClass = this.props.logoType === LogoType.MOBILE ? classes.mobileLogo : classes.logo;
        const logoClassName = classNames("headerLogo-logo", this.props.logoClassName, desktopOrMobileClass);
        const url = navigationVariables().logo.url;

        if (doubleLogoStrategy === "hidden") {
            return null;
        }

        return (
            <SmartLink to={url} className={classNames("headerLogo", this.props.className)}>
                <span className={classNames("headerLogo-logoFrame", classes.logoFrame)}>
                    <ThemeLogo
                        overwriteLogo={this.props.overwriteLogo}
                        alt={t("Vanilla")}
                        className={logoClassName}
                        type={this.props.logoType}
                    />
                </span>
            </SmartLink>
        );
    }
}
