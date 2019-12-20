/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import Backgrounds from "@library/layout/Backgrounds";
import getStore from "@library/redux/getStore";
import { ICoreStoreState } from "@library/redux/reducerRegistry";
import { storyBookClasses } from "@library/storybook/StoryBookStyles";
import { ThemeProvider } from "@library/theming/ThemeProvider";
import { blotCSS } from "@rich-editor/quill/components/blotStyles";
import React, { useContext, useState, useEffect, useLayoutEffect, useMemo, useCallback } from "react";
import { Provider } from "react-redux";
import { DeepPartial } from "redux";
import "../../scss/_base.scss";
import isEqual from "lodash/isEqual";

const errorMessage = "There was an error fetching the theme.";

function ErrorComponent() {
    return <p>{errorMessage}</p>;
}

interface IContext {
    storeState?: DeepPartial<ICoreStoreState>;
    useWrappers?: boolean;
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
}

export function storyWithConfig(config: Partial<IContext>, Component: React.ComponentType) {
    const HookWrapper = () => {
        useStoryConfig(config);
        return <Component />;
    };

    const StoryCaller = () => {
        return <HookWrapper />;
    };

    return StoryCaller;
}

export function StoryContextProvider(props: { children?: React.ReactNode }) {
    const [contextState, setContextState] = useState<IContext>({
        useWrappers: true,
    });
    const updateContext = useCallback(
        (value: Partial<IContext>) => {
            const newState = { ...contextState, ...value };
            if (!isEqual(newState, contextState)) {
                setContextState({ ...contextState, ...value });
            }
        },
        [setContextState, contextState],
    );
    const content = (
        <>
            <Backgrounds />
            {props.children}
        </>
    );

    const classes = storyBookClasses();
    blotCSS();
    return (
        <StoryContext.Provider value={{ ...contextState, updateContext }}>
            <Provider store={getStore()}>
                <ThemeProvider errorComponent={<ErrorComponent />} themeKey="theme-variables-dark">
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
