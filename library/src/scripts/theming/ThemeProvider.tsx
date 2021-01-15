/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { LoadStatus } from "@library/@types/api/core";
import { inputClasses } from "@library/forms/inputStyles";
import { useScrollOffset } from "@library/layout/ScrollOffsetContext";
import Loader from "@library/loaders/Loader";
import { ICoreStoreState } from "@library/redux/reducerRegistry";
import { useReduxActions } from "@library/redux/ReduxActions";
import ThemeActions from "@library/theming/ThemeActions";
import { prepareShadowRoot } from "@vanilla/dom-utils";
import React, { useEffect, useState } from "react";
import { useSelector } from "react-redux";
import { loadThemeFonts } from "./loadThemeFonts";
import { Backgrounds, BackgroundsProvider } from "@library/layout/Backgrounds";
import { BrowserRouter } from "react-router-dom";
import { useThemeCacheID } from "@library/styles/themeCache";

interface IProps {
    children: React.ReactNode;
    themeKey: string;
    errorComponent: React.ReactNode;
    variablesOnly?: boolean;
    disabled?: boolean;
    revisionID?: number;
}

let hasMounted = false;

export const ThemeProvider: React.FC<IProps> = (props: IProps) => {
    const { themeKey, disabled, variablesOnly, revisionID } = props;
    const { getAssets } = useReduxActions(ThemeActions);
    const { assets } = useSelector((state: ICoreStoreState) => state.theme);
    const { setTopOffset } = useScrollOffset();
    const { cacheID } = useThemeCacheID();

    const [ownThemeKey, setThemeKey] = useState({});
    // Trigger a state re-render when the theme key changes
    useEffect(() => {
        setThemeKey(themeKey);
    }, [themeKey, setThemeKey]);

    useEffect(() => {
        if (disabled) {
            return;
        }

        if (assets.status === LoadStatus.PENDING) {
            void getAssets(themeKey, revisionID);
            return;
        }

        if (assets.data && !hasMounted) {
            hasMounted = true;
            let themeHeader = document.getElementById("themeHeader");
            const themeFooter = document.getElementById("themeFooter");

            if (themeHeader) {
                themeHeader = prepareShadowRoot(themeHeader, true);

                // Apply the theme's header height to offset our panel layouts.
                setTopOffset(themeHeader.getBoundingClientRect().height);
            }

            if (themeFooter) {
                prepareShadowRoot(themeFooter, true);
            }

            loadThemeFonts();

            if (variablesOnly) {
                return;
            }
        }
    }, [assets, disabled, setTopOffset, variablesOnly, getAssets, themeKey, cacheID]);

    if (props.disabled || props.variablesOnly) {
        return <>{props.children}</>;
    }

    if ([LoadStatus.PENDING, LoadStatus.LOADING].includes(assets.status)) {
        return (
            <>
                <Backgrounds />
                {!variablesOnly && <Loader />}
            </>
        );
    }

    if (assets.status === LoadStatus.ERROR) {
        return (
            <>
                <Backgrounds />
                {props.errorComponent}
            </>
        );
    }

    if (!assets.data) {
        return null;
    }

    // Apply kludged input text styling everywhere.
    inputClasses().applyInputCSSRules();

    return (
        <>
            <BrowserRouter>
                <BackgroundsProvider>
                    <Backgrounds />
                    {props.children}
                </BackgroundsProvider>
            </BrowserRouter>
        </>
    );
};
