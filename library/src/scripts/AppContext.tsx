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
import { inheritHeightClass } from "@library/styles/styleHelpers";
import classNames from "classnames";
import { style } from "typestyle";
import { percent } from "csx";
import { LocaleProvider, ContentTranslationProvider } from "@vanilla/i18n";
import { SearchFilterContextProvider } from "@library/contexts/SearchFilterContext";
import { SearchContextProvider } from "@library/contexts/SearchContext";
import { TitleBarDeviceProvider } from "@library/layout/TitleBarContext";

interface IProps {
    children: React.ReactNode;
    variablesOnly?: boolean;
    noTheme?: boolean;
    noWrap?: boolean;
    errorComponent?: React.ReactNode;
}

/**
 * Core provider set for running most Vanilla components.
 */
export function AppContext(props: IProps) {
    const store = useMemo(() => getStore<ICoreStoreState>(), []);

    const rootStyle = style({
        $debugName: "appContext",
        width: percent(100),
    });

    const content = (
        <Provider store={store}>
            <LocaleProvider>
                <SearchContextProvider>
                    <ContentTranslationProvider>
                        <LiveAnnouncer>
                            <ThemeProvider
                                disabled={props.noTheme}
                                errorComponent={props.errorComponent || null}
                                themeKey={getMeta("ui.themeKey", "keystone")}
                                variablesOnly={props.variablesOnly}
                            >
                                <FontSizeCalculatorProvider>
                                    <SearchFilterContextProvider>
                                        <ScrollOffsetProvider scrollWatchingEnabled={false}>
                                            <TitleBarDeviceProvider>
                                                <DeviceProvider>{props.children}</DeviceProvider>
                                            </TitleBarDeviceProvider>
                                        </ScrollOffsetProvider>
                                    </SearchFilterContextProvider>
                                </FontSizeCalculatorProvider>
                            </ThemeProvider>
                        </LiveAnnouncer>
                    </ContentTranslationProvider>
                </SearchContextProvider>
            </LocaleProvider>
        </Provider>
    );

    if (props.noWrap) {
        return content;
    } else {
        return <div className={classNames("js-appContext", rootStyle, inheritHeightClass())}>{content}</div>;
    }
}
