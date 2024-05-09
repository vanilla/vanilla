/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useMemo, useEffect, useCallback } from "react";
import { LinkContextProvider } from "@library/routing/links/LinkContextProvider";
import { Router as ReactRouter, Switch, Route } from "react-router-dom";
import { formatUrl } from "@library/utility/appUtils";
import { createBrowserHistory, History } from "history";
import NotFoundPage from "@library/routing/NotFoundPage";
import { BackRoutingProvider } from "@library/routing/links/BackRoutingProvider";
import { initPageViewTracking, usePageChangeListener } from "@library/pageViews/pageViewTracking";
import { ErrorPageBoundary } from "@library/errorPages/ErrorPageBoundary";
import { useDispatch, useSelector } from "react-redux";
import RouteActions from "@library/RouteActions";
import { ICoreStoreState } from "@library/redux/reducerRegistry";
import { IError } from "@library/errorPages/CoreErrorMessages";
import { ErrorPage } from "@library/errorPages/ErrorComponent";
import { RouterRegistry } from "@library/Router.registry";

interface IProps {
    disableDynamicRouting?: boolean;
    sectionRoots?: string[];
    history?: History;
    useLayoutRouting?: boolean;
    onRouteChange?: (history: History) => void;
    ErrorPageComponent?: React.ComponentType<{ error?: Partial<IError> }>;
    children?: React.ReactNode;
}

export function Router(props: IProps) {
    const { onRouteChange } = props;
    const ownHistory = useMemo(() => createBrowserHistory({ basename: formatUrl("") }), []);
    const history = props.history ?? ownHistory;
    const dispatch = useDispatch();
    const clearInitialRouteError = useCallback(() => {
        dispatch(RouteActions.resetAC());
    }, [dispatch]);
    const initialRouteError = useSelector((state: ICoreStoreState) => state.route.error);
    const ErrorPageComponent = props.ErrorPageComponent ?? ErrorPage;

    useEffect(() => {
        initPageViewTracking(history);
    }, [history]);

    const pageChangeHandler = useCallback(() => {
        clearInitialRouteError();
        window.scrollTo(0, 0);
        onRouteChange?.(history);
    }, [history, onRouteChange, clearInitialRouteError]);

    usePageChangeListener(pageChangeHandler);

    let routes = (
        <ErrorPageBoundary>
            {initialRouteError ? (
                <ErrorPageComponent error={initialRouteError}></ErrorPageComponent>
            ) : (
                <Switch>
                    {RouterRegistry.routes}
                    <Route key="@not-found" component={NotFoundPage} />
                </Switch>
            )}
        </ErrorPageBoundary>
    );

    routes = (
        <BackRoutingProvider>
            {props.children}
            {routes}
        </BackRoutingProvider>
    );
    if (!props.disableDynamicRouting) {
        routes = (
            <LinkContextProvider
                useLayoutRouting={props.useLayoutRouting}
                linkContexts={(props.sectionRoots ?? ["/"])?.map((root) => formatUrl(root, true))}
            >
                {routes}
            </LinkContextProvider>
        );
    }

    return <ReactRouter history={history}>{routes}</ReactRouter>;
}
