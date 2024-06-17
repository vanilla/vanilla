/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { VanillaLogo } from "@library/icons/titleBar";
import { ICoreStoreState } from "@library/redux/reducerRegistry";
import React from "react";
import { connect } from "react-redux";
import { titleBarVariables } from "@library/headers/TitleBar.variables";
import { t } from "@library/utility/appUtils";
import { useThemeContext } from "./Theme.context";

export enum LogoType {
    DESKTOP = "logo",
    MOBILE = "mobileLogo",
}

function logoUrlFromState(themeState: ICoreStoreState["theme"], logoType: LogoType): string | null {
    const assets = themeState.assets.data || {};
    let logo;

    if (logoType === LogoType.DESKTOP) {
        logo = assets.logo || null;
    } else if (logoType === LogoType.MOBILE) {
        logo = assets.mobileLogo || null;
    }

    if (!logo) {
        return null;
    } else {
        return logo.url;
    }
}

interface IProps {
    alt: string;
    className?: string;
    type: LogoType;
    overwriteLogo?: string; // for storybook only
}

function ThemeLogo(props: IProps) {
    let content;

    const themeState = useThemeContext();

    const logoUrl = logoUrlFromState(themeState, props.type);

    const themeDesktopUrl = titleBarVariables().logo.desktop.url;
    const themeMobileUrl = titleBarVariables().logo.mobile.url;

    const isDesktop = props.type === LogoType.DESKTOP;
    const themeUrl = isDesktop ? themeDesktopUrl : themeMobileUrl;
    const finalUrl = props.overwriteLogo ?? themeUrl ?? logoUrl;

    if (finalUrl) {
        content = (
            <img
                className={props.className}
                src={finalUrl}
                alt={t("Home")}
                onLoad={() => {
                    // This helps the MegaMenu re-position
                    window.dispatchEvent(new Event("resize"));
                }}
            />
        );
    } else {
        content = <VanillaLogo className={props.className} isMobile={!isDesktop} />;
    }

    return content;
}

export default ThemeLogo;
