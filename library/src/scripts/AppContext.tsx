/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import React, { useMemo } from "react";
import getStore from "@library/redux/getStore";
import { ICoreStoreState } from "@library/redux/reducerRegistry";
import { createBrowserHistory } from "history";
import { formatUrl, getMeta, getRoutes } from "@library/utility/appUtils";
import { Provider } from "react-redux";
import { LiveAnnouncer } from "react-aria-live";
import { ThemeProvider } from "@library/theming/ThemeProvider";
import ErrorPage from "@knowledge/pages/ErrorPage";
import { ScrollOffsetProvider } from "@library/layout/ScrollOffsetContext";
import { DeviceProvider } from "@library/layout/DeviceContext";
import { LinkContextProvider } from "@library/routing/links/LinkContextProvider";
import { Router } from "react-router-dom";
import { FontSizeCalculatorProvider } from "@library/layout/pageHeadingContext";

interface IProps {
    children: React.ReactNode;
}

/**
 * Core provider set for running most Vanilla components.
 */
export function AppContext(props: IProps) {
    const store = useMemo(() => getStore<ICoreStoreState>(), []);

    return (
        <Provider store={store}>
            <LiveAnnouncer>
                <ThemeProvider errorComponent={<ErrorPage />} themeKey={getMeta("ui.themeKey", "keystone")}>
                    <FontSizeCalculatorProvider>
                        <ScrollOffsetProvider scrollWatchingEnabled={false}>
                            <DeviceProvider>{props.children}</DeviceProvider>
                        </ScrollOffsetProvider>
                    </FontSizeCalculatorProvider>
                </ThemeProvider>
            </LiveAnnouncer>
        </Provider>
    );
}
