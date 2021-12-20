/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { cx } from "@emotion/css";
import { getMeta } from "@library/utility/appUtils";
import React, { useContext, useMemo } from "react";

export interface IWidgetSectionContext {
    widgetClass: string;
    widgetWithContainerClass: string;
    headingBlockClass: string;
}

export const EMPTY_WIDGET_SECTION: IWidgetSectionContext = {
    widgetClass: "widget-dontUseCssOnMe",
    widgetWithContainerClass: "widgetWithContainer-dontUseCssOnMe",
    headingBlockClass: "headingBlock-dontUseCssOnMe",
};

export const LEGACY_WIDGET_SECTION: IWidgetSectionContext = {
    widgetClass: "widget-legacy",
    widgetWithContainerClass: "widgetWithContainer-legacy",
    headingBlockClass: "headingBlock-legacy",
};

export const WidgetSectionContext = React.createContext<IWidgetSectionContext>(EMPTY_WIDGET_SECTION);

export function useWidgetSectionClasses() {
    let contextValue = useContext(WidgetSectionContext);
    contextValue = useMemo(() => {
        const isLegacy = !getMeta("themeFeatures.DataDrivenTheme", false);
        const finalValue = contextValue;
        if (isLegacy) {
            for (const [key, value] of Object.entries(contextValue)) {
                finalValue[key] = cx(value, LEGACY_WIDGET_SECTION[key]);
            }
        }
        return finalValue;
    }, [contextValue]);

    return contextValue;
}
