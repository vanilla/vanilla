/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { LoadStatus } from "@library/@types/api";
import apiv2 from "@library/apiv2";
import { getMeta } from "@library/application";
import Loader from "@library/components/Loader";
import { ICoreStoreState } from "@library/state/reducerRegistry";
import ThemeActions from "@library/theming/ThemeActions";
import { IThemeVariables } from "@library/theming/themeReducer";
import React from "react";
import { connect } from "react-redux";
import getStore from "@library/state/getStore";
import Backgrounds from "@library/components/body/Backgrounds";
import { prepareShadowRoot } from "@library/dom";

export interface IWithThemeProps {
    theme: IThemeVariables;
}

class BaseThemeProvider extends React.Component<IProps> {
    public render() {
        const { variables } = this.props;
        switch (variables.status) {
            case LoadStatus.PENDING:
            case LoadStatus.LOADING:
                return <Loader />;
            case LoadStatus.ERROR:
                return this.props.errorComponent;
        }

        if (!variables.data) {
            return null;
        }

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

export function getThemeVariables() {
    const store = getStore<ICoreStoreState>();
    return store.getState().theme.variables.data || {};
}

interface IOwnProps {
    children: React.ReactNode;
    errorComponent: React.ReactNode;
}

type IProps = IOwnProps & ReturnType<typeof mapStateToProps> & ReturnType<typeof mapDispatchToProps>;

function mapStateToProps(state: ICoreStoreState, ownProps: IOwnProps) {
    return {
        variables: state.theme.variables,
    };
}

function mapDispatchToProps(dispatch: any) {
    const themeActions = new ThemeActions(dispatch, apiv2);
    return {
        requestData: () => themeActions.getVariables(getMeta("ui.themeKey", "default")),
    };
}

export const ThemeProvider = connect(
    mapStateToProps,
    mapDispatchToProps,
)(BaseThemeProvider);
