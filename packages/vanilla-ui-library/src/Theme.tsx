/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React, { useLayoutEffect, useMemo } from "react";
import { css } from "@emotion/css";
import { bodyStyleMixin, globalCSS } from "@library/layout/bodyStyles";
import { ThemeOverrideContext } from "@library/theming/ThemeOverrideContext";
import type { DeepPartial } from "redux";
import type { globalVariables } from "@library/styles/globalStyleVars";
import type { IThemeVariables } from "@library/theming/themeReducer";
import { ToastProvider } from "@library/features/toaster/ToastContext";

interface IThemeProps {
    theme: "dark" | "light";
    fontStack?: string[];
    children: React.ReactNode;
}

export function Theme(props: IThemeProps) {
    const {
        fontStack = [
            "system-ui",
            "-apple-system",
            "BlinkMacSystemFont",
            "Segoe UI",
            "Roboto",
            "Oxygen",
            "Ubuntu",
            "Cantarell",
            "Open Sans",
            "Helvetica Neue",
            "sans-serif",
        ],
    } = props;
    const overrides: IThemeVariables = useMemo(() => {
        return {
            global: {
                fonts: {
                    families: {
                        body: fontStack,
                        headings: fontStack,
                    },
                },
            } as DeepPartial<ReturnType<typeof globalVariables>>,
        };
    }, [fontStack.join(",")]);

    return (
        <ThemeOverrideContext.Provider
            value={{
                themeID: null,
                overridesVariables: {
                    global: {} as DeepPartial<typeof globalVariables>,
                },
            }}
        >
            <ToastProvider>
                <ThemeImpl {...props} />
            </ToastProvider>
            <div id="modals"></div>
        </ThemeOverrideContext.Provider>
    );
}

function ThemeImpl(props: IThemeProps) {
    const classname = useMemo(() => {
        return css({
            ...bodyStyleMixin(props.theme),
        });
    }, [props.theme]);

    useLayoutEffect(() => {
        globalCSS(false);
    });

    useLayoutEffect(() => {
        document.body.classList.add(classname);
        return () => {
            document.body.classList.remove(classname);
        };
    }, [classname]);

    return <>{props.children}</>;
}
