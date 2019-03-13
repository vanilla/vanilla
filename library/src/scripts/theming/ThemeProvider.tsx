/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { LoadStatus } from "@library/@types/api";
import apiv2 from "@library/apiv2";
import Backgrounds from "@library/components/body/Backgrounds";
import { inputClasses } from "@library/components/forms/inputStyles";
import Loader from "@library/components/Loader";
import { prepareShadowRoot } from "@library/dom";
import getStore from "@library/state/getStore";
import { ICoreStoreState } from "@library/state/reducerRegistry";
import ThemeActions from "@library/theming/ThemeActions";
import { IThemeVariables } from "@library/theming/themeReducer";
import React from "react";
import { connect } from "react-redux";

export interface IWithThemeProps {
    theme: IThemeVariables;
}

export enum LogoType {
    DESKTOP = "logo",
    MOBILE = "mobileLogo",
}

class BaseThemeProvider extends React.Component<IProps> {
    public render() {
        const { assets } = this.props;
        switch (assets.status) {
            case LoadStatus.PENDING:
            case LoadStatus.LOADING:
                return <Loader />;
            case LoadStatus.ERROR:
                return this.props.errorComponent;
        }

        if (!assets.data) {
            return null;
        }

        // Apply kludged input text styling everywhere.
        inputClasses().applyInputCSSRules();

        return (
            <>
                <Backgrounds />
                {this.props.children}
            </>
        );
    }

    public componentDidMount() {
        void this.props.requestData();
        const themeHeader = document.getElementById("themeHeader");
        const themeFooter = document.getElementById("themeFooter");

        if (themeHeader) {
            prepareShadowRoot(themeHeader, true);
        }

        if (themeFooter) {
            prepareShadowRoot(themeFooter, true);
        }
    }
}

export function getLogo(logoType: LogoType): string | null {
    const store = getStore<ICoreStoreState>();
    const assets = store.getState().theme.assets.data || {};
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

export function getThemeVariables() {
    const store = getStore<ICoreStoreState>();
    const assets = store.getState().theme.assets.data || {};
    const variables = assets.variables || {};
    return variables;
}

interface IOwnProps {
    children: React.ReactNode;
    themeKey: string;
    errorComponent: React.ReactNode;
}

type IProps = IOwnProps & ReturnType<typeof mapStateToProps> & ReturnType<typeof mapDispatchToProps>;

function mapStateToProps(state: ICoreStoreState, ownProps: IOwnProps) {
    return {
        assets: state.theme.assets,
    };
}

function mapDispatchToProps(dispatch: any, ownProps: IOwnProps) {
    const themeActions = new ThemeActions(dispatch, apiv2);
    return {
        requestData: () => themeActions.getAssets(ownProps.themeKey),
    };
}

export const ThemeProvider = connect(
    mapStateToProps,
    mapDispatchToProps,
)(BaseThemeProvider);
