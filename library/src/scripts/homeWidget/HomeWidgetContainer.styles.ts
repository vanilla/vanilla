/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ButtonTypes } from "@library/forms/buttonTypes";
import { HomeWidgetItemContentType, homeWidgetItemVariables } from "@library/homeWidget/HomeWidgetItem.styles";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { navLinksVariables } from "@library/navigation/navLinksStyles";
import { globalVariables } from "@library/styles/globalStyleVars";
import { shadowHelper } from "@library/styles/shadowHelpers";
import { BorderType, extendItemContainer } from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { IFont, IBackground, ISpacing } from "@library/styles/cssUtilsTypes";
import { Variables } from "@library/styles/Variables";
import { Mixins } from "@library/styles/Mixins";
import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { IThemeVariables } from "@library/theming/themeReducer";
import { calc, percent } from "csx";
import { CSSObject } from "@emotion/css";
export interface IHomeWidgetContainerOptions {
    noGutter?: boolean;
    outerBackground?: IBackground;
    innerBackground?: IBackground;
    borderType?: BorderType | "navLinks";
    maxWidth?: number | string;
    viewAll?: IViewAll;
    maxColumnCount?: number;
    subtitle?: {
        type?: "standard" | "overline";
        content?: string;
        font?: IFont;
        padding?: ISpacing;
    };
    description?: string;
    headerAlignment?: "left" | "center";
    contentAlignment?: "flex-start" | "center";
}

interface IViewAll {
    position?: "top" | "bottom";
    to?: string;
    name?: string;
    displayType?: ButtonTypes;
}

/**
 * @varGroup homeWidgetContainer
 * @title HomeWidget Container
 * @description The home widget container controls the grid of home widget items. It doesn't affect the individual items, instead focusing on the content around the items and their arrangement/placement together.
 */
export const homeWidgetContainerVariables = useThemeCache(
    (optionOverrides?: IHomeWidgetContainerOptions, forcedVars?: IThemeVariables) => {
        const makeVars = variableFactory("homeWidgetContainer", forcedVars);
        const globalVars = globalVariables(forcedVars);
        const layoutVars = layoutVariables(forcedVars);
        const itemVars = homeWidgetItemVariables({}, forcedVars);

        /**
         * @varGroup homeWidgetContainer.options
         * @commonTitle HomeWidget - Options
         * @description Control different variants for the HomeWidget. These options can affect multiple parts of the HomeWidget at once.
         */
        let options = makeVars(
            "options",
            {
                noGutter: false,
                outerBackground: Variables.background({}),
                innerBackground: Variables.background({}),
                borderType: BorderType.NONE as BorderType | "navLinks",
                maxWidth: layoutVariables().contentWidth,
                viewAll: {
                    to: undefined as string | undefined,
                    position: "bottom" as "top" | "bottom",
                    displayType: ButtonTypes.TEXT_PRIMARY,
                    name: "View All",
                },
                maxColumnCount: [
                    HomeWidgetItemContentType.TITLE_BACKGROUND,
                    HomeWidgetItemContentType.TITLE_DESCRIPTION_IMAGE,
                ].includes(itemVars.options.contentType)
                    ? 4
                    : 3,
                subtitle: {
                    type: "standard" as "standard" | "overline",
                    content: undefined as string | undefined,
                    padding: Variables.spacing({}),
                    font: Variables.font({}),
                },
                description: undefined as string | undefined,
                headerAlignment: "left" as "left" | "center",
                contentAlignment: "flex-start" as "flex-start" | "center",
            },
            optionOverrides,
        );

        options = makeVars(
            "options",
            {
                ...options,
                borderType:
                    options.innerBackground.color || options.innerBackground.image
                        ? BorderType.SHADOW
                        : BorderType.NONE,
                maxWidth: options.maxColumnCount <= 2 ? layoutVars.contentSizes.narrow : options.maxWidth,
            },
            optionOverrides,
        );

        options = makeVars(
            "options",
            {
                ...options,
                innerBackground: {
                    ...options.innerBackground,
                    color: options.borderType !== BorderType.NONE ? globalVars.body.backgroundImage.color : undefined,
                },
            },
            optionOverrides,
        );

        const title = makeVars("title", {
            /**
             * @var homeWidgetContainer.title.font
             * @type string
             * @expand font
             */
            font: Variables.font({}),
        });

        const navPaddings = navLinksVariables().item.padding;
        const mobileNavPaddings = navLinksVariables().item.paddingMobile;

        const bottomMultiplier = options.viewAll.position === "bottom" ? 1.5 : 2;
        const needsSpacing =
            options.outerBackground.color || options.outerBackground.image || options.borderType === "navLinks";
        const spacing = makeVars("spacing", {
            /**
             * @varGroup homeWidgetContainer.spacing.padding
             * @title HomeWidget Spacing
             * @expand spacing
             */
            padding: Variables.spacing({
                top: needsSpacing ? globalVars.gutter.size * 2 : globalVars.gutter.size,
                bottom: needsSpacing ? globalVars.gutter.size * bottomMultiplier : 0,
            }),
        });

        /**
         * @varGroup homeWidgetContainer.itemSpacing
         * @commonTitle HomeWidget Grid Spacing
         * @expand spacing
         */
        const itemSpacing = makeVars("itemSpacing", {
            /**
             * @var homeWidgetContainer.itemSpacing.horizontal
             * @description Sets the amount of padding on content in the HomeWidget Container. The higher padding, the more narrow the widget becomes.
             * @type string | number
             */
            horizontal: options.borderType === "navLinks" ? navPaddings.horizontal : globalVars.gutter.size,

            /**
             * @var homeWidgetContainer.itemSpacing.vertical
             * @description Sets the amount of space underneath the View All button row.
             * @type string | number
             */
            vertical: globalVars.gutter.size / 2,

            /**
             * @var homeWidgetContainer.itemSpacing.mobile
             * @description Sets the amount of horizontal padding the container has on mobile devices.
             * @type string | number
             */
            mobile: {
                horizontal: options.borderType === "navLinks" ? mobileNavPaddings.horizontal : globalVars.gutter.size,
            },
        });

        const horizontalSpacing = (itemSpacing.horizontal as number) / 2; // Cut in half to account for grid item spacing.
        const horizontalSpacingMobile = (itemSpacing.mobile.horizontal as number) / 2; // Cut in half to account for grid item spacing.

        /**
         * @varGroup homeWidgetContainer.grid
         * @commonTitle HomeWidget Grid
         */
        const grid = makeVars("grid", {
            padding: Variables.spacing({
                horizontal: horizontalSpacing,
                vertical: itemSpacing.vertical,
            }),

            paddingMobile: {
                horizontal: horizontalSpacingMobile,
            },
        });

        const gridItem = makeVars("gridItem", {
            /**
             * @varGroup homeWidgetContainer.gridItem.padding
             * @commonTitle Padding
             * @expand spacing
             */
            padding: Variables.spacing({
                horizontal: horizontalSpacing,
                vertical: itemSpacing.vertical,
            }),

            /**
             * @varGroup homeWidgetContainer.gridItem.paddingMobile
             * @commonTitle Mobile Padding
             * @expand spacing
             */
            paddingMobile: Variables.spacing({
                horizontal: horizontalSpacingMobile,
            }),
        });

        const description = makeVars("description", {
            /**
             * @varGroup homeWidgetContainer.description.font
             * @commonTitle Font
             * @expand font
             */
            font: Variables.font({}),

            /**
             * @varGroup homeWidgetContainer.description.padding
             * @commonTitle Padding
             * @expand spacing
             */
            padding: Variables.spacing({
                horizontal: calc(`${styleUnit((gridItem.padding.horizontal as number) * 2)}`),
                vertical: calc(`${styleUnit(gridItem.padding.vertical)}`),
                top: options.subtitle.content && options.subtitle.type === "standard" ? 0 : undefined,
            }),

            /**
             * @varGroup homeWidgetContainer.description.paddingMobile
             * @commonTitle Mobile Padding
             * @expand spacing
             */
            paddingMobile: Variables.spacing({
                horizontal: gridItem.padding.horizontal,
            }),
        });

        const mobileMediaQuery = layoutVariables().mediaQueries().oneColumnDown;

        return { options, spacing, itemSpacing, title, description, grid, gridItem, mobileMediaQuery };
    },
);

export const homeWidgetContainerClasses = useThemeCache((optionOverrides?: IHomeWidgetContainerOptions) => {
    const style = styleFactory("homeWidgetContainer");
    const globalVars = globalVariables();
    const vars = homeWidgetContainerVariables(optionOverrides);

    const root = style({
        ...Mixins.background(vars.options.outerBackground ?? {}),
    });

    // For navLinks style only.
    const separator = style("separator", {});

    const contentMixin: CSSObject = {
        ...Mixins.padding({
            vertical: vars.itemSpacing.vertical,
        }),
        ...(vars.options.borderType === "navLinks"
            ? extendItemContainer(navLinksVariables().linksWithHeadings.paddings.horizontal)
            : extendItemContainer(vars.itemSpacing.horizontal as number)),
    };

    const verticalContainer = style("verticalContainer", {
        ...Mixins.padding(vars.spacing.padding),
    });

    const container = style("container", {
        ...{
            "&&": {
                maxWidth: styleUnit(vars.options.maxWidth),
                margin: "0 auto",
                width: "100%",
            },
        },
    });

    const content = style("content", contentMixin);

    const borderedContent = style("borderedContent", {
        ...contentMixin,
        ...Mixins.padding({
            top: 0,
            horizontal: vars.itemSpacing.horizontal,
        }),
    });

    const borderStyling: CSSObject = (() => {
        switch (vars.options.borderType) {
            case BorderType.NONE:
                return {};
            case BorderType.BORDER:
                return {
                    borderRadius: globalVars.border.radius,
                    ...Mixins.border(),
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
            ...Mixins.background(vars.options.innerBackground),
            display: "flex",
            alignItems: "stretch",
            justifyContent: vars.options.contentAlignment ?? "flex-start",
            flexWrap: "wrap",
            ...Mixins.padding(vars.grid.padding),
        },
        borderStyling,
        vars.mobileMediaQuery(Mixins.padding(vars.grid.paddingMobile)),
    );

    const itemMixin: CSSObject = {
        flex: 1,
        flexBasis: percent(100 / vars.options.maxColumnCount),
    };

    const gridItem = style("gridItem", itemMixin);

    const gridItemSpacer = style("gridItemSpacer", {
        ...itemMixin,
        minWidth: styleUnit(homeWidgetItemVariables().sizing.minWidth),
    });

    const gridItemContent = style(
        "gridItemContent",
        {
            ...Mixins.padding(vars.gridItem.padding),
            height: percent(100),
        },
        vars.mobileMediaQuery(Mixins.padding(vars.gridItem.paddingMobile)),
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
            ...Mixins.padding({
                horizontal: vars.grid.padding.horizontal,
            }),
        },
        vars.mobileMediaQuery(Mixins.padding(vars.grid.paddingMobile)),
    );

    const title = style(
        "title",
        {
            flex: 1,
            ...Mixins.font(vars.title.font),
            ...Mixins.padding({
                horizontal: vars.gridItem.padding.horizontal,
            }),
            textAlign: vars.options.headerAlignment,
        },
        vars.mobileMediaQuery(
            Mixins.padding({
                horizontal: vars.gridItem.paddingMobile.horizontal,
            }),
        ),
    );

    const subtitle = style("subtitle", {
        ...Mixins.font({
            ...vars.options.subtitle.font,
            color: vars.options.subtitle.font.color
                ? vars.options.subtitle.font.color
                : vars.options.subtitle.type === "overline"
                ? globalVars.mainColors.primary
                : undefined,
            size: vars.options.subtitle.font.size
                ? vars.options.subtitle.font.size
                : vars.options.subtitle.type === "overline"
                ? 14
                : 16,
            weight: vars.options.subtitle.font.weight ? vars.options.subtitle.font.weight : 400,
            transform: vars.options.subtitle.font.transform
                ? vars.options.subtitle.font.transform
                : vars.options.subtitle.type === "overline"
                ? "uppercase"
                : undefined,
            letterSpacing: vars.options.subtitle.font.letterSpacing
                ? vars.options.subtitle.font.letterSpacing
                : vars.options.subtitle.type === "overline"
                ? 1
                : undefined,
        }),
        ...Mixins.padding({
            ...vars.options.subtitle.padding,
            horizontal: (vars.gridItem.padding.horizontal as number) * 2,
            top: vars.options.subtitle.padding.top ?? 12,
            bottom: vars.options.subtitle.padding.bottom ?? 20,
        }),
        textAlign: vars.options.headerAlignment,
    });

    const description = style(
        "description",
        {
            ...Mixins.padding(vars.description.padding),
            ...Mixins.font(vars.description.font),
            textAlign: vars.options.headerAlignment,
        },
        vars.mobileMediaQuery(Mixins.padding(vars.description.paddingMobile)),
    );
    const viewAll = style("viewAll", {
        ...{
            "&&": {
                ...Mixins.margin({
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
        ...{
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
        subtitle,
        description,
        viewAllContainer,
        viewAll,
        grid,
        gridItem,
        gridItemSpacer,
        gridItemContent,
        gridItemWidthConstraint,
    };
});
