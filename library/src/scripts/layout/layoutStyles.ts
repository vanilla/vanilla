/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { calc, color, percent, px, translateY, viewHeight } from "csx";
import { cssRule, media } from "typestyle";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { margins, paddings, sticky, unit } from "@library/styles/styleHelpers";
import { important } from "csx/lib/strings";
import { panelListClasses } from "@library/layout/panelListStyles";
import { titleBarVariables } from "@library/headers/titleBarStyles";
import { panelAreaClasses } from "@library/layout/panelAreaStyles";
import { NestedCSSProperties } from "typestyle/lib/types";
import { panelWidgetVariables } from "@library/layout/panelWidgetStyles";
import { panelBackgroundVariables } from "@library/layout/panelBackgroundStyles";
import { IThemeVariables } from "@library/theming/themeReducer";
import { getLayouts } from "@library/layout/types/layouts";
import { camelCaseToDash } from "@dashboard/compatibilityStyles";
import { IThreeColumnLayoutMediaQueries } from "@library/layout/types/threeColumn";

export enum LayoutTypes {
    THREE_COLUMNS = "three columns", // Dynamic layout with up to 3 columns that adjusts to its contents
    ONE_COLUMN_NARROW = "one column narrow", // Single column, but narrower than normal
    TWO_COLUMNS = "two columns", // Two column layout
    LEGACY_TWO_COLUMNS = "legacy two columns", // Legacy two column layout
    LEGACY_ONE_COLUMNS = "legacy one column", // Legacy one column layout
}

export const layoutVariables = useThemeCache((forcedVars?: IThemeVariables) => {
    const globalVars = globalVariables(forcedVars);
    const makeThemeVars = variableFactory("layouts", forcedVars);

    const colors = makeThemeVars("colors", {
        leftColumnBg: globalVars.mainColors.bg,
    });

    const gutter = makeThemeVars("gutter", {
        full: globalVars.constants.fullGutter, // 48
        size: globalVars.constants.fullGutter / 2, // 24
        halfSize: globalVars.constants.fullGutter / 4, // 12
        quarterSize: globalVars.constants.fullGutter / 8, // 6
    });

    const spacing = makeThemeVars("spacing", {
        margin: {
            top: 0,
            bottom: 0,
        },
        padding: {
            top: gutter.halfSize * 1.5,
        },
        extraPadding: {
            top: 32,
            bottom: 32,
            noBreadcrumbs: {},
            mobile: {
                noBreadcrumbs: {
                    top: 16,
                },
            },
        },
        largePadding: {
            top: 64,
        },
        offset: {
            left: -44,
            right: -36,
        },
        withPanelBackground: {
            gutter: 70,
        },
    });

    const typeVariables = {
        threeColumns: {} as any,
    };

    // Default widths
    const { panel, middleColumn } = globalVars;

    // Default 3 column layout that is dynamic and can be adapted to many situations with options
    const layoutVars = getLayouts({ gutter, globalVars, forcedVars });

    typeVariables.threeColumns = layoutVars.threeColumns.generateVariables({ middleColumn, panel });

    // Todo, remove type here and get it form context
    const mediaQueries = (type: LayoutTypes = LayoutTypes.THREE_COLUMNS) => {
        if (typeVariables[type]) {
            return typeVariables[type].mediaQueries();
        } else {
            return typeVariables[LayoutTypes.THREE_COLUMNS].mediaQueries();
        }
    };

    return {
        colors,
        gutter,
        spacing,
        types: typeVariables,
        mediaQueries,
    };
});

const layoutClasses = (props: { type: LayoutTypes }) => {
    const { type = LayoutTypes.THREE_COLUMNS } = props;
    switch (type) {
        case LayoutTypes.ONE_COLUMN_NARROW:
            return generateLayoutClasses({ type });
        case LayoutTypes.TWO_COLUMNS:
            return generateLayoutClasses({ type });
        case LayoutTypes.LEGACY_ONE_COLUMNS:
            return generateLayoutClasses({ type });
        case LayoutTypes.LEGACY_TWO_COLUMNS:
            return generateLayoutClasses({ type });
        default:
            return generateLayoutClasses({ type: LayoutTypes.THREE_COLUMNS });
    }
};

const generateLayoutClasses = useThemeCache((props: { type?: LayoutTypes }) => {
    const { type = LayoutTypes.THREE_COLUMNS } = props;
    const globalVars = globalVariables();
    const vars = layoutVariables();

    const layoutTypeVariables = vars.types[type];

    const mediaQueries = layoutTypeVariables.mediaQueries();
    const style = styleFactory("layout" + camelCaseToDash(type.replace(/\s+/g, "")));
    const classesPanelArea = panelAreaClasses();
    const classesPanelList = panelListClasses();

    const main = style("main", {
        minHeight: viewHeight(20),
        width: percent(100),
    });

    const root = style(
        {
            ...margins(layoutTypeVariables.spacing.margin),
            width: percent(100),
            $nest: {
                [`&.noBreadcrumbs > .${main}`]: {
                    paddingTop: unit(globalVars.gutter.size),
                    ...mediaQueries.oneColumnDown({
                        paddingTop: 0,
                    }),
                },
                "&.isOneCol": {
                    width: unit(layoutTypeVariables.middleColumnPaddedWidth()),
                    maxWidth: percent(100),
                    margin: "auto",
                    ...mediaQueries.oneColumnDown({
                        width: percent(100),
                    }),
                },
                "&.hasTopPadding": {
                    paddingTop: unit(layoutTypeVariables.spacing.extraPadding.top),
                },
                "&.hasTopPadding.noBreadcrumbs": {
                    paddingTop: unit(layoutTypeVariables.spacing.extraPadding.mobile.noBreadcrumbs.top),
                },
                "&.hasLargePadding": {
                    ...paddings(layoutTypeVariables.spacing.largePadding),
                },
            },
        },
        mediaQueries.oneColumnDown({
            $nest: {
                "&.hasTopPadding.noBreadcrumbs": {
                    paddingTop: unit(layoutTypeVariables.spacing.extraPadding.mobile.noBreadcrumbs.top),
                },
            },
        }),
    );

    const content = style("content", {
        display: "flex",
        flexGrow: 1,
        width: percent(100),
        justifyContent: "space-between",
    });

    const panel = style("panel", {
        width: percent(100),
        $nest: {
            [`& > .${classesPanelArea.root}:first-child .${classesPanelList.root}`]: {
                marginTop: unit(
                    (globalVars.fonts.size.title * globalVars.lineHeights.condensed) / 2 -
                        globalVariables().fonts.size.medium / 2,
                ),
            },
        },
    });

    const top = style("top", {
        width: percent(100),
        marginBottom: unit(globalVars.gutter.half),
    });

    const container = style("container", {
        display: "flex",
        flexWrap: "nowrap",
        alignItems: "flex-start",
        justifyContent: "space-between",
    });

    const fullWidth = style("fullWidth", {
        position: "relative",
        padding: 0,
    });

    const offset = panelBackgroundVariables().config.render
        ? layoutTypeVariables.spacing.withPanelBackground.gutter - panelWidgetVariables().spacing.padding * 2
        : 0;

    const leftColumn = style("leftColumn", {
        position: "relative",
        width: unit(layoutTypeVariables.panelPaddedWidth()),
        flexBasis: unit(layoutTypeVariables.panelPaddedWidth()),
        minWidth: unit(layoutTypeVariables.panelPaddedWidth()),
        paddingRight: unit(offset),
    });

    const rightColumn = style("rightColumn", {
        position: "relative",
        width: unit(layoutTypeVariables.panelPaddedWidth()),
        flexBasis: unit(layoutTypeVariables.panelPaddedWidth()),
        minWidth: unit(layoutTypeVariables.panelPaddedWidth()),
        overflow: "initial",
        paddingLeft: unit(offset),
    });

    const middleColumn = style("middleColumn", {
        justifyContent: "space-between",
        flexGrow: 1,
        width: percent(100),
        maxWidth: percent(100),
        paddingBottom: unit(layoutTypeVariables.spacing.extraPadding.bottom),
        ...mediaQueries.oneColumnDown(paddings({ left: important(0), right: important(0) })),
    });

    const middleColumnMaxWidth = style("middleColumnMaxWidth", {
        $nest: {
            "&.hasAdjacentPanel": {
                flexBasis: calc(`100% - ${unit(layoutTypeVariables.panelPaddedWidth())}`),
                maxWidth: calc(`100% - ${unit(layoutTypeVariables.panelPaddedWidth())}`),
                ...mediaQueries.oneColumnDown({
                    flexBasis: percent(100),
                    maxWidth: percent(100),
                }),
            },
            "&.hasTwoAdjacentPanels": {
                flexBasis: calc(`100% - ${unit(layoutTypeVariables.panelPaddedWidth() * 2)}`),
                maxWidth: calc(`100% - ${unit(layoutTypeVariables.panelPaddedWidth() * 2)}`),
                ...mediaQueries.oneColumnDown({
                    flexBasis: percent(100),
                    maxWidth: percent(100),
                }),
            },
        },
    });

    const breadcrumbs = style("breadcrumbs", {});

    const isSticky = style(
        "isSticky",
        {
            ...sticky(),
            height: percent(100),
            $unique: true,
        },
        mediaQueries.oneColumnDown({
            position: "relative",
            top: "auto",
            left: "auto",
            bottom: "auto",
        }),
    );

    // To remove when we have overlay styles converted
    cssRule(`.overlay .${root}.noBreadcrumbs .${main}`, {
        paddingTop: 0,
    });

    const breadcrumbsContainer = style("breadcrumbs", {
        paddingBottom: unit(10),
    });

    return {
        root,
        content,
        top,
        main,
        container,
        fullWidth,
        leftColumn,
        rightColumn,
        middleColumn,
        middleColumnMaxWidth,
        panel,
        isSticky,
        breadcrumbs,
        breadcrumbsContainer,
    };
});
