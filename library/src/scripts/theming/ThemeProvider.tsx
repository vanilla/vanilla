/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { LoadStatus } from "@library/@types/api/core";
import apiv2 from "@library/apiv2";
import { inputClasses } from "@library/forms/inputStyles";
import Backgrounds from "@library/layout/Backgrounds";
import { useScrollOffset } from "@library/layout/ScrollOffsetContext";
import Loader from "@library/loaders/Loader";
import { ICoreStoreState } from "@library/redux/reducerRegistry";
import { useReduxActions } from "@library/redux/ReduxActions";
import ThemeActions from "@library/theming/ThemeActions";
import { prepareShadowRoot } from "@vanilla/dom-utils";
import React, { useEffect } from "react";
import { useSelector } from "react-redux";
import { loadThemeFonts } from "./loadThemeFonts";

interface IProps {
    children: React.ReactNode;
    themeKey: string;
    errorComponent: React.ReactNode;
    variablesOnly?: boolean;
    disabled?: boolean;
}

export function ThemeProvider(props: IProps) {
    const { themeKey, disabled, variablesOnly } = props;
    const { getAssets } = useReduxActions(ThemeActions);
    const { assets } = useSelector((state: ICoreStoreState) => state.theme);
    const { setTopOffset } = useScrollOffset();

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
                console.log("Measure theme header", themeHeader, themeHeader.getBoundingClientRect());
                // For some reason the measurements are not applied immediately to the header
                // Waiting 1 tick works though.
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

    if (props.disabled) {
        return props.children;
    }
    if (props)
        switch (assets.status) {
            case LoadStatus.PENDING:
            case LoadStatus.LOADING:
                return <Loader />;
            case LoadStatus.ERROR:
                return props.errorComponent;
        }

    if (!assets.data) {
        return null;
    }

    if (props.variablesOnly) {
        return props.children;
    }

    // Apply kludged input text styling everywhere.
    inputClasses().applyInputCSSRules();

    return (
        <>
            <Backgrounds />
            {props.children}
        </>
    );
}

function mapDispatchToProps(dispatch: any, ownProps: IProps) {
    const themeActions = new ThemeActions(dispatch, apiv2);
    return {
        requestData: () => themeActions.getAssets(ownProps.themeKey),
    };
}
