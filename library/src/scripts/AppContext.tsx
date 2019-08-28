/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { DeviceProvider } from "@library/layout/DeviceContext";
import { FontSizeCalculatorProvider } from "@library/layout/pageHeadingContext";
import { ScrollOffsetProvider } from "@library/layout/ScrollOffsetContext";
import getStore from "@library/redux/getStore";
import { ICoreStoreState } from "@library/redux/reducerRegistry";
import { ThemeProvider } from "@library/theming/ThemeProvider";
import { getMeta } from "@library/utility/appUtils";
import React, { useMemo } from "react";
import { LiveAnnouncer } from "react-aria-live";
import { Provider } from "react-redux";

interface IProps {
    children: React.ReactNode;
    variablesOnly?: boolean;
    noTheme?: boolean;
    errorComponent?: React.ReactNode;
}

/**
 * Core provider set for running most Vanilla components.
 */
export function AppContext(props: IProps) {
    const store = useMemo(() => getStore<ICoreStoreState>(), []);

    return (
        <Provider store={store}>
            <LiveAnnouncer>
                <ThemeProvider
                    disabled={props.noTheme}
                    errorComponent={props.errorComponent || null}
                    themeKey={getMeta("ui.themeKey", "keystone")}
                    variablesOnly={props.variablesOnly}
                >
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
