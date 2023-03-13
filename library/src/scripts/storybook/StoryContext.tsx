/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useLayout } from "@dashboard/layout/layoutSettings/LayoutSettings.hooks";
import { LoadStatus } from "@library/@types/api/core";
import { bannerVariables } from "@library/banner/Banner.variables";
import { CallToAction } from "@library/callToAction/CallToAction";
import { userContentVariables } from "@library/content/UserContent.variables";
import { tileVariables } from "@library/features/tiles/Tile.variables";
import { tilesVariables } from "@library/features/tiles/Tiles.variables";
import { inputClasses } from "@library/forms/inputStyles";
import { HomeWidget } from "@library/homeWidget/HomeWidget";
import { Backgrounds } from "@library/layout/Backgrounds";
import { DeviceProvider } from "@library/layout/DeviceContext";
import { ScrollOffsetProvider } from "@library/layout/ScrollOffsetContext";
import { TitleBarDeviceProvider } from "@library/layout/TitleBarContext";
import { WidgetLayout } from "@library/layout/WidgetLayout";
import { listVariables } from "@library/lists/List.variables";
import { listItemVariables } from "@library/lists/ListItem.variables";
import { quickLinksVariables } from "@library/navigation/QuickLinks.variables";
import getStore, { createRootReducer, hasStore } from "@library/redux/getStore";
import { ICoreStoreState } from "@library/redux/reducerRegistry";
import { storyBookClasses } from "@library/storybook/StoryBookStyles";
import { globalVariables } from "@library/styles/globalStyleVars";
import { resetThemeCache } from "@library/styles/themeCache";
import { ThemeProvider } from "@library/theming/ThemeProvider";
import { INITIAL_THEME_STATE } from "@library/theming/themeReducer";
import { addComponent, _mountComponents } from "@library/utility/componentRegistry";
import { blotCSS } from "@rich-editor/quill/components/blotStyles";
import merge from "lodash/merge";
import React, { useCallback, useContext, useEffect, useLayoutEffect, useState } from "react";
import { LiveAnnouncer } from "react-aria-live";
import { Provider } from "react-redux";
import { MemoryRouter } from "react-router";
import { DeepPartial } from "redux";
import "../../scss/_base.scss";

const errorMessage = "There was an error fetching the theme.";

function ErrorComponent() {
    return <p>{errorMessage}</p>;
}

export interface IStoryTheme {
    global?: DeepPartial<ReturnType<typeof globalVariables>>;
    tiles?: DeepPartial<ReturnType<typeof tilesVariables>>;
    tile?: DeepPartial<ReturnType<typeof tileVariables>>;
    banner?: DeepPartial<ReturnType<typeof bannerVariables>>;
    userContent?: DeepPartial<ReturnType<typeof userContentVariables>>;
    quickLinks?: DeepPartial<ReturnType<typeof quickLinksVariables>>;
    list?: DeepPartial<ReturnType<typeof listVariables>>;
    listItem?: DeepPartial<ReturnType<typeof listItemVariables>>;
    [key: string]: any;
}

interface IContext {
    storeState?: DeepPartial<ICoreStoreState>;
    themeVars?: IStoryTheme;
    useWrappers?: boolean;
    refreshKey?: string;
}

const StoryContext = React.createContext<IContext & { updateContext: (value: Partial<IContext>) => void }>({
    updateContext: () => {},
});

export const NO_WRAPPER_CONFIG = {
    useWrappers: false,
};

export function useStoryConfig(value: Partial<IContext>) {
    const context = useContext(StoryContext);
    useLayoutEffect(() => {
        context.updateContext(value);
        return () => {
            // Clear the context.
            context.updateContext({});
        };
    }, []);

    return context.refreshKey;
}

export function storyWithConfig(config: Partial<IContext>, Component: React.ComponentType): any {
    const HookWrapper = () => {
        const refreshKey = useStoryConfig(config);
        return <Component key={refreshKey} />;
    };

    const StoryCaller = () => {
        return <HookWrapper />;
    };

    return StoryCaller;
}

const INITIAL_STORY_STATE = {
    theme: {
        ...INITIAL_THEME_STATE,
        assets: {
            data: {
                variables: {
                    data: {},
                    type: "json",
                },
            },
            status: LoadStatus.SUCCESS,
        },
    },
};

const defaultState = createRootReducer()(INITIAL_STORY_STATE, { type: "initial" });

export function StoryContextProvider(props: {
    children?: React.ReactNode;
    noWrappers?: boolean;
    noWidgetLayout?: boolean;
}) {
    const [contextState, setContextState] = useState<IContext>({
        useWrappers: true,
        storeState: INITIAL_STORY_STATE,
    });
    const [themeKey, setThemeKey] = useState("");

    useLayoutEffect(() => {
        addComponent("HomeWidget", HomeWidget, { overwrite: true });
        addComponent("CallToAction", CallToAction, { overwrite: true });
        _mountComponents(document.body);
    });

    const updateContext = useCallback(
        (value: Partial<IContext>) => {
            const storeState = value.storeState ?? {};
            // Get the default states
            storeState.theme = {
                assets: {
                    data: {
                        variables: {
                            data: (value.themeVars as any) ?? {},
                            type: "json",
                        },
                    },
                    status: LoadStatus.SUCCESS,
                },
            };

            const newState = {
                ...contextState,
                ...value,
                storeState: storeState,
            };

            setContextState(newState);
            getStore(merge({}, defaultState, newState.storeState), true);
            setThemeKey(resetThemeCache().toString());
        },
        [contextState, themeKey],
    );

    const store = getStore();
    const classes = storyBookClasses();
    blotCSS();
    inputClasses().applyInputCSSRules();

    let content = (
        <>
            <Backgrounds />
            {props.children}
        </>
    );

    if (contextState.useWrappers && !props.noWrappers) {
        content = (
            <div className={classes.containerOuter}>
                <div className={classes.containerInner}>{content}</div>
            </div>
        );
    }

    if (!props.noWidgetLayout) {
        content = <WidgetLayout>{content}</WidgetLayout>;
    }

    return (
        <StoryContext.Provider value={{ ...contextState, updateContext, refreshKey: themeKey }}>
            <Provider store={store}>
                <MemoryRouter>
                    <ThemeProvider disabled errorComponent={<ErrorComponent />} themeKey={themeKey}>
                        <ScrollOffsetProvider>
                            <DeviceProvider>
                                <LiveAnnouncer>
                                    <TitleBarDeviceProvider>{content}</TitleBarDeviceProvider>
                                </LiveAnnouncer>
                            </DeviceProvider>
                        </ScrollOffsetProvider>
                    </ThemeProvider>
                </MemoryRouter>
            </Provider>
        </StoryContext.Provider>
    );
}
