/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useThemeCache, variableFactory, styleFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { CsxBackgroundOptions } from "csx/lib/types";
import {
    BorderType,
    EMPTY_BACKGROUND,
    IBackground,
    unit,
    background,
    margins,
    EMPTY_FONTS,
    fonts,
    paddings,
} from "@library/styles/styleHelpers";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { percent } from "csx";

export enum ViewAllDisplayType {
    BUTTON_PRIMARY = "buttonPrimary",
    LINK = "link",
}

export interface IHomeWidgetContainerOptions {
    outerBackground?: IBackground;
    innerBackground?: IBackground;
    borderType?: BorderType;
    maxWidth?: number | string;
    viewAll?: IViewAll;
    maxColumnCount?: number;
}

interface IViewAll {
    position?: "top" | "bottom";
    to?: string;
    displayType?: ViewAllDisplayType;
}

export const homeWidgetContainerVariables = useThemeCache((optionOverrides?: IHomeWidgetContainerOptions) => {
    const makeVars = variableFactory("homeWidgetContainer");
    const globalVars = globalVariables();
    const layoutVars = layoutVariables();

    let options = makeVars(
        "options",
        {
            outerBackground: {
                ...EMPTY_BACKGROUND,
            },
            innerBackground: {
                ...EMPTY_BACKGROUND,
            },
            borderType: BorderType.NONE,
            maxWidth: layoutVars.contentSizes.full,
            viewAll: {
                position: "top",
                displayType: ViewAllDisplayType.LINK,
            },
            maxColumnCount: 3,
        },
        optionOverrides,
    );

    options = makeVars(
        "options",
        {
            ...options,
            maxWidth: options.maxColumnCount <= 2 ? layoutVars.contentSizes.narrow : layoutVars.contentSizes.full,
        },
        optionOverrides,
    );

    const title = makeVars("title", {
        font: {
            ...EMPTY_FONTS,
        },
    });

    const spacing = makeVars("spacing", {
        gutter: globalVars.gutter.size,
    });

    return { options, spacing, title };
});

export const homeWidgetContainerClasses = useThemeCache((optionOverrides?: IHomeWidgetContainerOptions) => {
    const style = styleFactory("homeWidgetContainer");
    const vars = homeWidgetContainerVariables(optionOverrides);

    const root = style({
        ...background(vars.options.outerBackground ?? {}),
    });

    const content = style("content", {
        maxWidth: unit(vars.options.maxWidth),
        ...margins({
            vertical: 0,
            horizontal: "auto",
        }),
    });

    const grid = style("grid", {
        display: "flex",
        alignItems: "stretch",
        justifyContent: "flex-start",
        flexWrap: "wrap",
        padding: unit(vars.spacing.gutter / 2),
    });

    const gridItem = style("gridItem", {
        flex: 1,
        flexBasis: percent(100 / vars.options.maxColumnCount),
    });

    const gridItemContent = style("gridItemContent", {
        padding: unit(vars.spacing.gutter / 2),
    });

    const gridItemWidthConstraint = useThemeCache((maxWidth: number) =>
        style("gridItemWidthConstraint", {
            maxWidth: maxWidth > 0 ? maxWidth : "initial",
        }),
    );

    const title = style("title", {
        ...fonts(vars.title.font),
        ...paddings({
            horizontal: vars.spacing.gutter,
        }),
    });

    return { root, content, title, grid, gridItem, gridItemContent, gridItemWidthConstraint };
});
