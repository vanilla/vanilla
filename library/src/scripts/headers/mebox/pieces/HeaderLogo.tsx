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
import { TitleBarDevices, useTitleBarDevice } from "@library/layout/TitleBarContext";

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
export default function HeaderLogo(props: IHeaderLogo) {
    const { doubleLogoStrategy } = titleBarVariables().logo;
    const classes = titleBarLogoClasses();
    const desktopOrMobileClass = props.logoType === LogoType.MOBILE ? classes.mobileLogo : classes.logo;
    const logoClassName = classNames("headerLogo-logo", props.logoClassName, desktopOrMobileClass);
    const url = navigationVariables().logo.url;
    const device = useTitleBarDevice();

    if (device === TitleBarDevices.FULL && doubleLogoStrategy === "mobile-only") {
        return <></>;
    }

    if (doubleLogoStrategy === "hidden") {
        return <></>;
    }

    return (
        <SmartLink to={url} className={classNames("headerLogo", props.className)}>
            <span className={classNames("headerLogo-logoFrame", classes.logoFrame)}>
                <ThemeLogo
                    overwriteLogo={props.overwriteLogo}
                    alt={t("Vanilla")}
                    className={logoClassName}
                    type={props.logoType}
                />
            </span>
        </SmartLink>
    );
}
