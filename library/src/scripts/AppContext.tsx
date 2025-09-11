import { ContentTranslationProvider, LocaleProvider } from "@vanilla/i18n";
import React, { ComponentType, useMemo } from "react";

import { ApiV2Context } from "@library/apiv2";
import { AttachmentIntegrationsContextProvider } from "@library/features/discussions/integrations/Integrations.context";
import { DeviceProvider } from "@library/layout/DeviceContext";
/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { DiscussionCheckboxProvider } from "@library/features/discussions/DiscussionCheckboxContext";
import { EntryLinkContextProvider } from "@library/contexts/EntryLinkContext";
import { ErrorPage } from "@library/errorPages/ErrorComponent";
import { FontSizeCalculatorProvider } from "@library/layout/pageHeadingContext";
import { ICoreStoreState } from "@library/redux/reducerRegistry";
import { LiveAnnouncer } from "react-aria-live";
import { PermissionsContextProvider } from "@library/features/users/PermissionsContext";
import { Provider } from "react-redux";
import { ReactQueryContext } from "@library/ReactQueryContext";
import { ReduxCurrentUserContextProvider } from "./features/users/userHooks";
import { ReduxThemeContextProvider } from "./theming/Theme.context";
import { ScrollOffsetProvider } from "@library/layout/ScrollOffsetContext";
import { SearchContextProvider } from "@library/contexts/SearchContext";
import { SiteSectionContextProvider } from "./utility/SiteSectionContext";
import { ThemeProvider } from "@library/theming/ThemeProvider";
import { TitleBarDeviceProvider } from "@library/layout/TitleBarContext";
import { ToastProvider } from "@library/features/toaster/ToastContext";
import { TranslationDebugProvider } from "@library/TranslationDebugProvider";
import classNames from "classnames";
import { css } from "@emotion/css";
import { getMeta } from "@library/utility/appUtils";
import getStore from "@library/redux/getStore";
import { inheritHeightClass } from "@library/styles/styleHelpers";
import { percent } from "csx";

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
            ReactQueryContext,
            [Provider, { store }],
            ReduxCurrentUserContextProvider,
            ApiV2Context,
            SiteSectionContextProvider,
            PermissionsContextProvider,
            LocaleProvider,
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
            ReduxThemeContextProvider,
            FontSizeCalculatorProvider,
            ...ExtraContextProviders,
            TitleBarDeviceProvider,
            EntryLinkContextProvider,
            DeviceProvider,
            ToastProvider,
            DiscussionCheckboxProvider,
            AttachmentIntegrationsContextProvider,
            TranslationDebugProvider,
        ],
        props.children,
    );

    if (props.noWrap) {
        return content;
    } else {
        return <div className={classNames("js-appContext", rootStyle, inheritHeightClass())}>{content}</div>;
    }
}
