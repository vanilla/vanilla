/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import ErrorPage from "@knowledge/routes/ErrorPage";
import { LoadStatus } from "@library/@types/api";
import apiv2 from "@library/apiv2";
import { getMeta } from "@library/application";
import Loader from "@library/components/Loader";
import { ICoreStoreState } from "@library/state/reducerRegistry";
import ThemeActions from "@library/theming/ThemeActions";
import { IThemeVariables } from "@library/theming/themeReducer";
import React from "react";
import { connect } from "react-redux";
import { Optionalize } from "@library/@types/utils";
import getStore from "@library/state/getStore";

export interface IWithThemeProps {
    theme: IThemeVariables;
}

const ThemeContext = React.createContext<IWithThemeProps>({ theme: {} });

class BaseThemeContextProvider extends React.Component<IProps> {
    public render() {
        const { variables } = this.props;
        switch (variables.status) {
            case LoadStatus.PENDING:
            case LoadStatus.LOADING:
                return <Loader />;
            case LoadStatus.ERROR:
                return <ErrorPage />;
        }

        if (!variables.data) {
            return null;
        }

        return <ThemeContext.Provider value={{ theme: variables.data }}>{this.props.children}</ThemeContext.Provider>;
    }

    public componentDidMount() {
        void this.props.requestData();
    }
}

/**
 * HOC to inject ThemeContext as props.
 *
 * @param WrappedComponent - The component to wrap
 */
export function withTheme<T extends IWithThemeProps = IWithThemeProps>(WrappedComponent: React.ComponentType<T>) {
    const displayName = WrappedComponent.displayName || WrappedComponent.name || "Component";
    const ComponentWithDevice = (props: Optionalize<T, IWithThemeProps>) => {
        return (
            <ThemeContext.Consumer>
                {context => {
                    return <WrappedComponent {...context} {...props} />;
                }}
            </ThemeContext.Consumer>
        );
    };
    ComponentWithDevice.displayName = `withTheme(${displayName})`;
    return ComponentWithDevice;
}

export function getThemeVariables() {
    const store = getStore<ICoreStoreState>();
    return store.getState().theme.variables;
}

interface IOwnProps {
    children: React.ReactNode;
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

export const ThemeContextProvider = connect(
    mapStateToProps,
    mapDispatchToProps,
)(BaseThemeContextProvider);
