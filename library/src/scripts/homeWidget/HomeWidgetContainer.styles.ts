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
    IBorderStyles,
    ISimpleBorderStyle,
    borders,
} from "@library/styles/styleHelpers";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { percent } from "csx";
import { NestedCSSProperties } from "typestyle/lib/types";
import { shadowHelper } from "@library/styles/shadowHelpers";

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
            borderType:
                options.innerBackground.color || options.innerBackground.image ? BorderType.SHADOW : BorderType.NONE,
            maxWidth: options.maxColumnCount <= 2 ? layoutVars.contentSizes.narrow : layoutVars.contentSizes.full,
        },
        optionOverrides,
    );

    options = makeVars(
        "options",
        {
            ...options,
            innerBackground: {
                color: options.borderType !== BorderType.NONE ? globalVars.mainColors.bg : undefined,
            },
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
    const globalVars = globalVariables();
    const vars = homeWidgetContainerVariables(optionOverrides);

    const root = style({
        ...background(vars.options.outerBackground ?? {}),
    });

    const contentMixin: NestedCSSProperties = {
        maxWidth: unit(vars.options.maxWidth),
        ...paddings({
            vertical: vars.spacing.gutter,
        }),
        ...margins({
            vertical: 0,
            horizontal: "auto",
        }),
    };

    const content = style("content", contentMixin);

    const borderedContent = style("borderedContent", {
        ...contentMixin,
        maxWidth: unit(vars.options.maxWidth + vars.spacing.gutter * 2),
        ...paddings({
            top: 0,
            horizontal: vars.spacing.gutter,
        }),
    });

    const borderStyling: NestedCSSProperties = (() => {
        switch (vars.options.borderType) {
            case BorderType.NONE:
                return {};
            case BorderType.BORDER:
                return {
                    borderRadius: globalVars.border.radius,
                    ...borders(),
                };
            case BorderType.SHADOW:
                return {
                    borderRadius: globalVars.border.radius,
                    ...shadowHelper().embed(),
                };
        }
    })();

    const grid = style(
        "grid",
        {
            ...background(vars.options.innerBackground),
            display: "flex",
            alignItems: "stretch",
            justifyContent: "flex-start",
            flexWrap: "wrap",
            padding: unit(vars.spacing.gutter / 2),
        },
        borderStyling,
    );

    const gridItem = style("gridItem", {
        flex: 1,
        flexBasis: percent(100 / vars.options.maxColumnCount),
    });

    const gridItemContent = style("gridItemContent", {
        padding: unit(vars.spacing.gutter / 2),
        height: percent(100),
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

    return { root, content, borderedContent, title, grid, gridItem, gridItemContent, gridItemWidthConstraint };
});
