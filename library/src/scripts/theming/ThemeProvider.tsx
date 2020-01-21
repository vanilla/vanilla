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
import {ErrorPage} from "@library/errorPages/ErrorComponent";
import {DefaultError} from "@library/errorPages/ErrorMessagePage";

interface IProps {
    children: React.ReactNode;
    themeKey: string;
    errorComponent: React.ReactNode;
    variablesOnly?: boolean;
    disabled?: boolean;
}

export const ThemeProvider: React.FC<IProps> = (props: IProps) => {
    const { themeKey, disabled, variablesOnly } = props;
    const { getAssets } = useReduxActions(ThemeActions);
    const { assets } = useSelector((state: ICoreStoreState) => state.theme);
    const { setTopOffset } = useScrollOffset();

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
            void getAssets(themeKey);
            return;
        }

        if (assets.data) {
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

            if (variablesOnly) {
                return;
            }

            loadThemeFonts();
        }
    }, [assets, disabled, setTopOffset, variablesOnly, getAssets, themeKey]);

    if (props.disabled || props.variablesOnly) {
        return <>{props.children}</>;
    }

    if ([LoadStatus.PENDING, LoadStatus.LOADING].includes(assets.status)) {
        return <Loader />;
    }

    if (assets.status === LoadStatus.SUCCESS) {
        console.log('',assets);
        return(
            <BrowserRouter>
                <ErrorPage defaultError={DefaultError.NOT_FOUND} code={404} />
            </BrowserRouter>
        );
    }

    if (!assets.data) {
        return null;
    }

    // Apply kludged input text styling everywhere.
    inputClasses().applyInputCSSRules();
    console.log(assets.status);
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
