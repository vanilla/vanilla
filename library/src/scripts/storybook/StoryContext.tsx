/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import getStore, { resetStore } from "@library/redux/getStore";
import { ICoreStoreState } from "@library/redux/reducerRegistry";
import { storyBookClasses } from "@library/storybook/StoryBookStyles";
import { ThemeProvider } from "@library/theming/ThemeProvider";
import { blotCSS } from "@rich-editor/quill/components/blotStyles";
import React, { useContext, useState, useLayoutEffect, useMemo, useCallback } from "react";
import { Provider } from "react-redux";
import { DeepPartial } from "redux";
import "../../scss/_base.scss";
import isEqual from "lodash/isEqual";
import { resetThemeCache } from "@library/styles/themeCache";
import { LoadStatus } from "@library/@types/api/core";
import { resetStoreState } from "@library/__tests__/testStoreState";
import merge from "lodash/merge";
import { Backgrounds } from "@library/layout/Backgrounds";
import { globalVariables } from "@library/styles/globalStyleVars";
import { tileVariables } from "@library/features/tiles/Tile.variables";
import { tilesVariables } from "@library/features/tiles/Tiles.variables";
import { bannerVariables } from "@library/banner/bannerStyles";
import { inputClasses } from "@library/forms/inputStyles";
import { userContentVariables } from "@library/content/userContentStyles";
import { quickLinksVariables } from "@library/navigation/QuickLinks.variables";
import { _mountComponents, addComponent } from "@library/utility/componentRegistry";
import { HomeWidget } from "@library/homeWidget/HomeWidget";

const errorMessage = "There was an error fetching the theme.";

function ErrorComponent() {
    return <p>{errorMessage}</p>;
}

interface IContext {
    storeState?: DeepPartial<ICoreStoreState>;
    themeVars?: {
        global?: DeepPartial<ReturnType<typeof globalVariables>>;
        tiles?: DeepPartial<ReturnType<typeof tilesVariables>>;
        tile?: DeepPartial<ReturnType<typeof tileVariables>>;
        banner?: DeepPartial<ReturnType<typeof bannerVariables>>;
        userContent?: DeepPartial<ReturnType<typeof userContentVariables>>;
        quickLinks?: DeepPartial<ReturnType<typeof quickLinksVariables>>;
        [key: string]: any;
    };
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
    }, [context, value]);
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

export function StoryContextProvider(props: { children?: React.ReactNode }) {
    const [contextState, setContextState] = useState<IContext>({
        useWrappers: true,
        storeState: {},
    });
    const [themeKey, setThemeKey] = useState("");

    useLayoutEffect(() => {
        addComponent("HomeWidget", HomeWidget, { overwrite: true, bypassPortalManager: true });
        _mountComponents(document.body);
    });

    const updateContext = useCallback(
        (value: Partial<IContext>) => {
            const themeState: DeepPartial<ICoreStoreState> = {
                theme: {
                    assets: {
                        data: {
                            variables: {
                                data: (value.themeVars as any) ?? {},
                                type: "json",
                            },
                        },
                        status: LoadStatus.SUCCESS,
                    },
                },
            };
            const newState = {
                ...contextState,
                ...value,
                storeState: merge(value.storeState ?? {}, themeState),
            };
            if (!isEqual(newState, contextState)) {
                setContextState(newState);
                resetStoreState(newState.storeState);
                setThemeKey(resetThemeCache().toString());
            }
        },
        [contextState],
    );
    const content = (
        <>
            <Backgrounds />
            {props.children}
        </>
    );

    const classes = storyBookClasses();
    blotCSS();
    inputClasses().applyInputCSSRules();

    return (
        <StoryContext.Provider value={{ ...contextState, updateContext, refreshKey: themeKey }}>
            <Provider store={getStore(contextState.storeState)}>
                <ThemeProvider variablesOnly errorComponent={<ErrorComponent />} themeKey={themeKey}>
                    {contextState.useWrappers ? (
                        <div className={classes.containerOuter}>
                            <div className={classes.containerInner}>{content}</div>
                        </div>
                    ) : (
                        content
                    )}
                </ThemeProvider>
            </Provider>
        </StoryContext.Provider>
    );
}
