/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ButtonTypes } from "@library/forms/buttonTypes";
import { homeWidgetItemVariables } from "@library/homeWidget/HomeWidgetItem.styles";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { navLinksVariables } from "@library/navigation/navLinksStyles";
import { globalVariables } from "@library/styles/globalStyleVars";
import { shadowHelper } from "@library/styles/shadowHelpers";
import {
    backgroundHelper,
    borders,
    BorderType,
    EMPTY_BACKGROUND,
    EMPTY_FONTS,
    EMPTY_SPACING,
    extendItemContainer,
    fonts,
    IBackground,
    margins,
    paddings,
    unit,
} from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { percent } from "csx";
import { NestedCSSProperties } from "typestyle/lib/types";

export interface IHomeWidgetContainerOptions {
    outerBackground?: IBackground;
    innerBackground?: IBackground;
    borderType?: BorderType | "navLinks";
    maxWidth?: number | string;
    viewAll?: IViewAll;
    maxColumnCount?: number;
}

interface IViewAll {
    position?: "top" | "bottom";
    to?: string;
    name?: string;
    displayType?: ButtonTypes;
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
            borderType: BorderType.NONE as BorderType | "navLinks",
            maxWidth: layoutVariables().contentWidth,
            viewAll: {
                to: undefined as string | undefined,
                position: "bottom" as "top" | "bottom",
                displayType: ButtonTypes.TEXT_PRIMARY,
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
            maxWidth: options.maxColumnCount <= 2 ? layoutVars.contentSizes.narrow : options.maxWidth,
        },
        optionOverrides,
    );

    options = makeVars(
        "options",
        {
            ...options,
            innerBackground: {
                color: options.borderType !== BorderType.NONE ? globalVars.body.backgroundImage.color : undefined,
            },
        },
        optionOverrides,
    );

    const title = makeVars("title", {
        font: {
            ...EMPTY_FONTS,
        },
    });

    const navPaddings = navLinksVariables().item.padding;
    const mobileNavPaddings = navLinksVariables().item.paddingMobile;

    const bottomMultiplier = options.viewAll.position === "bottom" ? 1.5 : 2;
    const needsSpacing =
        options.outerBackground.color || options.outerBackground.image || options.borderType === "navLinks";
    const spacing = makeVars("spacing", {
        padding: {
            ...EMPTY_SPACING,
            top: needsSpacing ? globalVars.gutter.size * 2 : 0,
            bottom: needsSpacing ? globalVars.gutter.size * bottomMultiplier : 0,
        },
    });

    const itemSpacing = makeVars("itemSpacing", {
        ...EMPTY_SPACING,
        horizontal: options.borderType === "navLinks" ? navPaddings.horizontal : globalVars.gutter.size,
        vertical: globalVars.gutter.size,
        mobile: {
            ...EMPTY_SPACING,
            horizontal: options.borderType === "navLinks" ? mobileNavPaddings.horizontal : globalVars.gutter.size,
        },
    });

    const horizontalSpacing = itemSpacing.horizontal / 2; // Cut in half to account for grid item spacing.
    const horizontalSpacingMobile = itemSpacing.mobile.horizontal / 2; // Cut in half to account for grid item spacing.

    const grid = makeVars("grid", {
        padding: {
            ...EMPTY_SPACING,
            horizontal: horizontalSpacing,
            vertical: itemSpacing.vertical,
        },
        paddingMobile: {
            horizontal: horizontalSpacingMobile,
        },
    });

    const gridItem = makeVars("gridItem", {
        padding: {
            ...EMPTY_SPACING,
            horizontal: horizontalSpacing,
            vertical: itemSpacing.vertical,
        },
        paddingMobile: {
            ...EMPTY_SPACING,
            horizontal: horizontalSpacingMobile,
        },
    });

    const mobileMediaQuery = layoutVariables().mediaQueries().oneColumnDown;

    return { options, spacing, itemSpacing, title, grid, gridItem, mobileMediaQuery };
});

export const homeWidgetContainerClasses = useThemeCache((optionOverrides?: IHomeWidgetContainerOptions) => {
    const style = styleFactory("homeWidgetContainer");
    const globalVars = globalVariables();
    const vars = homeWidgetContainerVariables(optionOverrides);

    const root = style({
        ...backgroundHelper(vars.options.outerBackground ?? {}),
    });

    // For navLinks style only.
    const separator = style("separator", {});

    const contentMixin: NestedCSSProperties = {
        ...paddings({
            vertical: vars.itemSpacing.vertical,
        }),
        ...(vars.options.borderType === "navLinks"
            ? extendItemContainer(navLinksVariables().linksWithHeadings.paddings.horizontal)
            : extendItemContainer(vars.itemSpacing.horizontal)),
    };

    const verticalContainer = style("verticalContainer", {
        ...paddings(vars.spacing.padding),
    });

    const container = style("container", {
        $nest: {
            "&&": {
                maxWidth: unit(vars.options.maxWidth),
                margin: "0 auto",
            },
        },
    });

    const content = style("content", contentMixin);

    const borderedContent = style("borderedContent", {
        ...contentMixin,
        ...paddings({
            top: 0,
            horizontal: vars.itemSpacing.horizontal,
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
            default:
                return {};
        }
    })();

    const grid = style(
        "grid",
        {
            ...backgroundHelper(vars.options.innerBackground),
            display: "flex",
            alignItems: "stretch",
            justifyContent: "flex-start",
            flexWrap: "wrap",
            ...paddings(vars.grid.padding),
        },
        borderStyling,
        vars.mobileMediaQuery(paddings(vars.grid.paddingMobile)),
    );

    const itemMixin: NestedCSSProperties = {
        flex: 1,
        flexBasis: percent(100 / vars.options.maxColumnCount),
    };

    const gridItem = style("gridItem", itemMixin);

    const gridItemSpacer = style("gridItemSpacer", {
        ...itemMixin,
        minWidth: unit(homeWidgetItemVariables().sizing.minWidth),
    });

    const gridItemContent = style(
        "gridItemContent",
        {
            ...paddings(vars.gridItem.padding),
            height: percent(100),
        },
        vars.mobileMediaQuery(paddings(vars.gridItem.paddingMobile)),
    );

    const gridItemWidthConstraint = useThemeCache((maxWidth: number) =>
        style("gridItemWidthConstraint", {
            maxWidth: maxWidth > 0 ? maxWidth : "initial",
        }),
    );

    const viewAllContainer = style(
        "viewAllContainer",
        {
            display: "flex",
            justifyContent: "space-between",
            alignItems: "center",
            ...paddings({
                horizontal: vars.grid.padding.horizontal,
            }),
        },
        vars.mobileMediaQuery(paddings(vars.grid.paddingMobile)),
    );

    const title = style(
        "title",
        {
            flex: 1,
            ...fonts(vars.title.font),
            ...paddings({
                horizontal: vars.gridItem.padding.horizontal,
            }),
        },
        vars.mobileMediaQuery(
            paddings({
                horizontal: vars.gridItem.paddingMobile.horizontal,
            }),
        ),
    );

    const viewAll = style("viewAll", {
        $nest: {
            "&&": {
                ...margins({
                    horizontal: vars.options.borderType === "navLinks" ? 0 : vars.gridItem.padding.horizontal,
                }),
            },
            "&:first-child": {
                marginLeft: "auto",
            },
        },
    });

    const viewAllContent = style("viewAllContent", {
        ...contentMixin,
        paddingTop: 0,
        marginTop: -vars.itemSpacing.vertical,
        $nest: {
            [`.${borderedContent} + &`]: {
                marginTop: 0,
            },
        },
    });

    return {
        root,
        separator,
        verticalContainer,
        container,
        content,
        borderedContent,
        viewAllContent,
        title,
        viewAllContainer,
        viewAll,
        grid,
        gridItem,
        gridItemSpacer,
        gridItemContent,
        gridItemWidthConstraint,
    };
});
