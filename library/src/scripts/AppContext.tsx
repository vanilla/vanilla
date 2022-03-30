/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { DiscussionCheckboxProvider } from "@library/features/discussions/DiscussionCheckboxContext";
import { DeviceProvider } from "@library/layout/DeviceContext";
import { FontSizeCalculatorProvider } from "@library/layout/pageHeadingContext";
import { ScrollOffsetProvider } from "@library/layout/ScrollOffsetContext";
import getStore from "@library/redux/getStore";
import { ICoreStoreState } from "@library/redux/reducerRegistry";
import { ThemeProvider } from "@library/theming/ThemeProvider";
import { getMeta } from "@library/utility/appUtils";
import React, { ComponentType, useMemo } from "react";
import { LiveAnnouncer } from "react-aria-live";
import { Provider } from "react-redux";
import { inheritHeightClass } from "@library/styles/styleHelpers";
import classNames from "classnames";
import { percent } from "csx";
import { LocaleProvider, ContentTranslationProvider } from "@vanilla/i18n";
import { SearchContextProvider } from "@library/contexts/SearchContext";
import { TitleBarDeviceProvider } from "@library/layout/TitleBarContext";
import { ErrorPage } from "@library/errorPages/ErrorComponent";
import { BannerContextProvider } from "@library/banner/BannerContext";
import { SearchFormContextProvider } from "@library/search/SearchFormContextProvider";
import { EntryLinkContextProvider } from "@library/contexts/EntryLinkContext";
import { css } from "@emotion/css";
import { ApiV2Context } from "@library/apiv2";
import { ToastProvider } from "@library/features/toaster/ToastContext";
import { SearchSourcesContextProvider } from "@library/search/SearchSourcesContextProvider";

interface IProps {
    children: React.ReactNode;
    variablesOnly?: boolean;
    noTheme?: boolean;
    noWrap?: boolean;
    errorComponent?: React.ReactNode;
}

type Composable = ComponentType | [ComponentType, { [key: string]: any }];

let ExtraContextProviders: Composable[] = [];

export function registerContextProvider(provider: Composable) {
    ExtraContextProviders.unshift(provider);
}

function composeProviders(providers: Composable[], children) {
    return providers.reverse().reduce((acc, cur) => {
        const [Provider, props] = Array.isArray(cur) ? [cur[0], cur[1]] : [cur, {}];
        return <Provider {...props}>{acc}</Provider>;
    }, children);
}

/**
 * Core provider set for running most Vanilla components.
 */
export function AppContext(props: IProps) {
    const store = useMemo(() => getStore<ICoreStoreState>(), []);

    const rootStyle = css({
        label: "appContext",
        width: percent(100),
    });

    const content = composeProviders(
        [
            [Provider, { store }],
            ApiV2Context,
            LocaleProvider,
            SearchSourcesContextProvider,
            SearchContextProvider,
            ContentTranslationProvider,
            LiveAnnouncer,
            [ScrollOffsetProvider, { scrollWatchingEnabled: false }],
            [
                ThemeProvider,
                {
                    disabled: props.noTheme,
                    errorComponent: <ErrorPage />,
                    themeKey: getMeta("ui.themeKey", "keystone"),
                    variablesOnly: props.variablesOnly,
                },
            ],
            FontSizeCalculatorProvider,
            ...ExtraContextProviders,
            SearchFormContextProvider,
            TitleBarDeviceProvider,
            BannerContextProvider,
            EntryLinkContextProvider,
            DeviceProvider,
            ToastProvider,
            DiscussionCheckboxProvider,
        ],
        props.children,
    );

    if (props.noWrap) {
        return content;
    } else {
        return <div className={classNames("js-appContext", rootStyle, inheritHeightClass())}>{content}</div>;
    }
}
