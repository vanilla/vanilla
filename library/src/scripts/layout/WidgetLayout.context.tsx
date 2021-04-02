/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React, { useContext } from "react";

export interface IWidgetLayoutContext {
    widgetClass: string;
    widgetWithContainerClass: string;
    headingBlockClass: string;
}

export const EMPTY_WIDGET_LAYOUT: IWidgetLayoutContext = {
    widgetClass: "widget-dontUseCssOnMe",
    widgetWithContainerClass: "widgetWithContainer-dontUseCssOnMe",
    headingBlockClass: "headingBlock-dontUseCssOnMe",
};

export const WidgetLayoutContext = React.createContext<IWidgetLayoutContext>(EMPTY_WIDGET_LAYOUT);

export function useWidgetLayoutClasses() {
    return useContext(WidgetLayoutContext);
}
