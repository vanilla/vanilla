/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { VanillaLogo } from "@library/icons/titleBar";
import { ICoreStoreState } from "@library/redux/reducerRegistry";
import React from "react";
import { connect } from "react-redux";
import { titleBarVariables } from "@library/headers/titleBarStyles";

export enum LogoType {
    DESKTOP = "logo",
    MOBILE = "mobileLogo",
}

class ThemeLogo extends React.Component<IProps> {
    public render() {
        let content;

        const themeDesktopUrl = titleBarVariables().logo.desktop.url;
        const themeMobileUrl = titleBarVariables().logo.mobile.url;

        const themeUrl = this.props.type === LogoType.DESKTOP ? themeDesktopUrl : themeMobileUrl;
        const finalUrl = themeUrl ?? this.props.logoUrl;

        if (finalUrl) {
            content = <img className={this.props.className} src={finalUrl} />;
        } else {
            content = <VanillaLogo className={this.props.className} />;
        }

        return content;
    }
}

function logoUrlFromState(state: ICoreStoreState, logoType: LogoType): string | null {
    const assets = state.theme.assets.data || {};
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

interface IOwnProps {
    alt: string;
    className?: string;
    type: LogoType;
}

type IProps = IOwnProps & ReturnType<typeof mapStateToProps>;

function mapStateToProps(state: ICoreStoreState, ownProps: IOwnProps) {
    return {
        logoUrl: logoUrlFromState(state, ownProps.type),
    };
}

export default connect(mapStateToProps)(ThemeLogo);
