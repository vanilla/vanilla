/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { CSSObject } from "@emotion/css";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { HomeWidgetItemContentType, homeWidgetItemVariables } from "@library/homeWidget/HomeWidgetItem.styles";
import { pageHeadingBoxVariables, SubtitleType } from "@library/layout/PageHeadingBox.variables";
import { panelLayoutVariables } from "@library/layout/PanelLayout.variables";
import { navLinksVariables } from "@library/navigation/navLinksStyles";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { IBackground } from "@library/styles/cssUtilsTypes";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { shadowHelper } from "@library/styles/shadowHelpers";
import { BorderType, extendItemContainer, negativeUnit } from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { getPixelNumber, styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { Variables } from "@library/styles/Variables";
import { IThemeVariables } from "@library/theming/themeReducer";
import { percent } from "csx";
export interface IHomeWidgetContainerOptions {
    outerBackground?: IBackground;
    innerBackground?: IBackground;
    borderType?: BorderType | "navLinks";
    maxWidth?: number | string;
    viewAll?: IViewAll;
    maxColumnCount?: number;
    // @deprecated
    subtitle?: {
        type: SubtitleType;
        content?: string;
    };
    // @deprecated
    description?: string;
    headerAlignment?: "left" | "center";
    contentAlignment?: "flex-start" | "center";
    isGrid?: boolean;
    isCarousel?: boolean;
}

interface IViewAll {
    position?: "top" | "bottom";
    to?: string;
    onClick?: (e) => void;
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
        const itemVars = homeWidgetItemVariables({}, forcedVars);
        const pageHeadingVars = pageHeadingBoxVariables();

        /**
         * @varGroup homeWidgetContainer.options
         * @commonTitle HomeWidget - Options
         * @description Control different variants for the HomeWidget. These options can affect multiple parts of the HomeWidget at once.
         */
        let options = makeVars(
            "options",
            {
                outerBackground: Variables.background({}),
                innerBackground: Variables.background({}),
                borderType: BorderType.NONE as BorderType | "navLinks",
                maxWidth: "100%",
                viewAll: {
                    onClick: undefined,
                    to: undefined as string | undefined,
                    position: "bottom" as "top" | "bottom",
                    displayType: ButtonTypes.TEXT_PRIMARY,
                    name: "View All",
                },
                maxColumnCount: [
                    HomeWidgetItemContentType.TITLE_BACKGROUND,
                    HomeWidgetItemContentType.TITLE_DESCRIPTION_IMAGE,
                    HomeWidgetItemContentType.TITLE_BACKGROUND_DESCRIPTION,
                ].includes(itemVars.options.contentType)
                    ? 4
                    : 3,
                subtitle: {
                    type: pageHeadingVars.options.subtitleType,
                    // FIXME take this out of varialbes entirely. Pretty weird.
                    content: undefined as string | undefined,
                },
                description: undefined as string | undefined,
                headerAlignment: pageHeadingVars.options.alignment as "left" | "center",
                contentAlignment: "flex-start" as "flex-start" | "center",
                isGrid: false,
                isCarousel: false,
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
                isGrid: options.isCarousel ? false : options.isGrid,
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

        const navPaddings = navLinksVariables().item.padding;
        const mobileNavPaddings = navLinksVariables().item.paddingMobile;

        /**
         * @varGroup homeWidgetContainer.itemSpacing
         * @commonTitle HomeWidget Grid Spacing
         * @expand spacing
         */
        const itemSpacing = makeVars(
            "itemSpacing",
            {
                /**
                 * @var homeWidgetContainer.itemSpacing.horizontal
                 * @description Sets the amount of padding on content in the HomeWidget Container. The higher padding, the more narrow the widget becomes.
                 * @type string | number
                 */
                horizontal: options.borderType === "navLinks" ? navPaddings.horizontal : globalVars.gutter.size,

                /**
                 * @var homeWidgetContainer.itemSpacing.vertical
                 * @description Set the vertical spacing between items in the grid.
                 * @type string | number
                 */
                vertical: globalVars.gutter.size,

                /**
                 * @var homeWidgetContainer.itemSpacing.mobile
                 * @description Sets the amount of horizontal padding the container has on mobile devices.
                 * @type string | number
                 */
                mobile: {
                    horizontal:
                        options.borderType === "navLinks" ? mobileNavPaddings.horizontal : globalVars.gutter.size,
                },
            },
            !options.isGrid ? { horizontal: 0, vertical: globalVars.gutter.size / 2, mobile: { horizontal: 0 } } : {},
        );

        const hasVisibleContainer =
            Variables.boxHasOutline(
                Variables.box({
                    background: options.innerBackground,
                    borderType: options.borderType as BorderType,
                }),
            ) && options.borderType !== "navLinks";

        const mobileMediaQuery = panelLayoutVariables().mediaQueries().oneColumnDown;

        return { options, itemSpacing, mobileMediaQuery, hasVisibleContainer };
    },
);

export const homeWidgetContainerClasses = useThemeCache((optionOverrides?: IHomeWidgetContainerOptions) => {
    const style = styleFactory("homeWidgetContainer");
    const globalVars = globalVariables();
    const vars = homeWidgetContainerVariables(optionOverrides);
    const navLinkVars = navLinksVariables();

    const root = style(
        {
            ...Mixins.background(vars.options.outerBackground ?? {}),
            color: ColorsUtils.colorOut(globalVars.getFgForBg(vars.options.outerBackground.color)),
            width: "100%",
        },
        vars.options.borderType === "navLinks" &&
            Mixins.margin({
                vertical: navLinkVars.item.padding.vertical,
                top: navLinkVars.item.padding.top,
                bottom: navLinkVars.item.padding.bottom,
            }),
    );

    // For navLinks style only.
    const separator = style(
        "separator",
        extendItemContainer(getPixelNumber(navLinkVars.linksWithHeadings.paddings.horizontal) * 2),
        vars.mobileMediaQuery(extendItemContainer(0)),
    );

    const halfHorizontalSpacing = getPixelNumber(vars.itemSpacing.horizontal) / 2;
    const halfHorizontalSpacingMobile = getPixelNumber(vars.itemSpacing.mobile.horizontal) / 2;

    const contentMixin: CSSObject = {
        ...extendItemContainer(getPixelNumber(vars.itemSpacing.horizontal)),
        ...vars.mobileMediaQuery(extendItemContainer(getPixelNumber(vars.itemSpacing.mobile.horizontal))),
    };

    const container = style(
        "container",
        {
            maxWidth: styleUnit(vars.options.maxWidth),
            margin: "0 auto",
            width: "100%",
        },
        vars.options.borderType === "navLinks" && {
            ...extendItemContainer(navLinkVars.linksWithHeadings.paddings.horizontal),
        },
        vars.mobileMediaQuery(extendItemContainer(0)),
    );

    const content = style("content", contentMixin);

    const itemWrapper = style(
        "borderedContent",
        vars.hasVisibleContainer && Mixins.padding({ horizontal: halfHorizontalSpacing * 2 }),
    );

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
            ...Mixins.padding({
                horizontal: halfHorizontalSpacing,
            }),
        },
        vars.hasVisibleContainer
            ? {
                  paddingBottom: vars.itemSpacing.vertical,
              }
            : {
                  marginTop: negativeUnit(vars.itemSpacing.vertical),
              },
        borderStyling,
        vars.mobileMediaQuery(
            Mixins.padding({
                horizontal: halfHorizontalSpacingMobile,
            }),
        ),
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
            ...Mixins.padding({
                horizontal: halfHorizontalSpacing,
                top: vars.itemSpacing.vertical,
            }),
            height: percent(100),
        },
        vars.mobileMediaQuery(
            Mixins.padding({
                horizontal: halfHorizontalSpacingMobile,
            }),
        ),
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
            justifyContent: "flex-end",
            alignItems: "center",
            ...Mixins.padding({
                horizontal: halfHorizontalSpacing * 2,
            }),
            marginTop: vars.itemSpacing.vertical,
        },
        vars.mobileMediaQuery(Mixins.padding({ horizontal: halfHorizontalSpacingMobile * 2 })),
    );
    return {
        root,
        separator,
        container,
        content,
        itemWrapper,
        viewAllContainer,
        grid,
        gridItem,
        gridItemSpacer,
        gridItemContent,
        gridItemWidthConstraint,
    };
});
