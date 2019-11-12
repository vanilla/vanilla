/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { LoadStatus } from "@library/@types/api/core";
import apiv2 from "@library/apiv2";
import Backgrounds from "@library/layout/Backgrounds";
import { inputClasses } from "@library/forms/inputStyles";
import Loader from "@library/loaders/Loader";
import { prepareShadowRoot } from "@vanilla/dom-utils";
import { ICoreStoreState } from "@library/redux/reducerRegistry";
import ThemeActions from "@library/theming/ThemeActions";
import { IThemeVariables } from "@library/theming/themeReducer";
import React, { Children } from "react";
import { connect } from "react-redux";
import { loadThemeFonts } from "./loadThemeFonts";

export interface IWithThemeProps {
    theme: IThemeVariables;
}

class BaseThemeProvider extends React.Component<IProps> {
    public render() {
        const { assets } = this.props;
        if (this.props.disabled) {
            return this.props.children;
        }
        if (this.props)
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

        if (this.props.variablesOnly) {
            return this.props.children;
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
        if (this.props.disabled) {
            return;
        }
        void this.props.requestData();

        if (this.props.variablesOnly) {
            return;
        }

        const themeHeader = document.getElementById("themeHeader");
        const themeFooter = document.getElementById("themeFooter");

        if (themeHeader) {
            prepareShadowRoot(themeHeader, true);
        }

        if (themeFooter) {
            prepareShadowRoot(themeFooter, true);
        }

        loadThemeFonts();
    }
}

interface IOwnProps {
    children: React.ReactNode;
    themeKey: string;
    errorComponent: React.ReactNode;
    variablesOnly?: boolean;
    disabled?: boolean;
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

export const ThemeProvider = connect(mapStateToProps, mapDispatchToProps)(BaseThemeProvider);
