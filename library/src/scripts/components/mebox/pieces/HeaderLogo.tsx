/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { vanillaLogo } from "@library/components/icons/header";
import SmartLink from "@library/components/navigation/SmartLink";
import { vanillaHeaderLogoClasses } from "@library/styles/vanillaHeaderStyles";
import ThemeLogo, { LogoType } from "@library/theming/ThemeLogo";
import { t } from "@library/application";

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
    public render() {
        const classes = vanillaHeaderLogoClasses();
        const logoClassName = classNames("headerLogo-logo", this.props.logoClassName, classes.logo);

        return (
            <SmartLink to={this.props.to} className={classNames("headerLogo", classes.link, this.props.className)}>
                <span className={classNames("headerLogo-logoFrame", classes.logoFrame)}>
                    <ThemeLogo alt={t("Vanilla")} className={logoClassName} type={this.props.logoType} />
                </span>
            </SmartLink>
        );
    }
}
