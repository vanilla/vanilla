/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { calc, important, percent, viewHeight } from "csx";
import { cssRule, media } from "@library/styles/styleShim";
import { styleFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { negativeUnit, sticky } from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { panelBackgroundVariables } from "@library/layout/PanelBackground.variables";
import { SectionTypes } from "@library/layout/types/interface.layoutTypes";
import { IMediaQueryFunction, IOneColumnVariables } from "@library/layout/types/interface.panelLayout";
import { logError } from "@vanilla/utils";
import { Mixins } from "@library/styles/Mixins";
import { oneColumnVariables } from "./Section.variables";
import { css, CSSObject } from "@emotion/css";

export type ISectionClasses = ReturnType<typeof generateSectionClasses>;

export const doNothingWithMediaQueries = (styles: any) => {
    logError("Media queries are undefined, unable to set the following styles: ", styles);
    return {};
};

export const generateSectionClasses = (props: {
    vars: IOneColumnVariables;
    name: string;
    mediaQueries: IMediaQueryFunction;
}) => {
    const { vars, name, mediaQueries } = props;
    const globalVars = globalVariables();
    const style = styleFactory(name);

    const main = css({
        width: percent(100),
    });

    const mobileRootPadding = {
        oneColumnDown: {
            marginTop: globalVars.spacer.pageComponentCompact,
        },
    };
    const root = style({
        position: "relative",
        width: percent(100),
        // Pull up by our panel widget spacing.
        "&&": {
            marginTop: globalVars.spacer.mainLayout,
            ...mediaQueries({
                [SectionTypes.TWO_COLUMNS]: mobileRootPadding,
                [SectionTypes.THREE_COLUMNS]: mobileRootPadding,
            }),
        },
        [`&.noBreadcrumbs > .${main}`]: {
            // Offset for our widget spacers.
            marginTop: negativeUnit(globalVars.widget.padding),
        },
    });

    const content = style("content", {
        display: "flex",
        flexGrow: 1,
        width: percent(100),
        justifyContent: "space-between",
    });

    const panel = style("panel", {
        width: percent(100),
        ...{
            [`& > .panelArea:first-child .panelList`]: {
                marginTop: styleUnit(
                    (globalVars.fonts.size.title * globalVars.lineHeights.condensed) / 2 -
                        globalVariables().fonts.size.medium / 2,
                ),
            },
        },
    });

    const top = style("top", {
        width: percent(100),
        marginBottom: styleUnit(globalVars.gutter.half),
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
        ? oneColumnVariables().panelLayoutSpacing.withPanelBackground.gutter - globalVars.widget.padding * 2
        : 0;

    const leftColumn = style("leftColumn", {
        position: "relative",
        width: styleUnit(vars.panel.paddedWidth),
        flexBasis: styleUnit(vars.panel.paddedWidth),
        minWidth: styleUnit(vars.panel.paddedWidth),
        paddingRight: styleUnit(offset),
    });

    const rightColumn = style("rightColumn", {
        position: "relative",
        width: styleUnit(vars.panel.paddedWidth),
        flexBasis: styleUnit(vars.panel.paddedWidth),
        minWidth: styleUnit(vars.panel.paddedWidth),
        overflow: "initial",
        paddingLeft: styleUnit(offset),
    });

    const mainColumn = style("mainColumn", {
        justifyContent: "space-between",
        flexGrow: 1,
        width: percent(100),
        maxWidth: percent(100),
        ...mediaQueries({
            [SectionTypes.THREE_COLUMNS]: {
                oneColumnDown: {
                    ...Mixins.padding({
                        left: important(0),
                        right: important(0),
                    }),
                },
            },
        }),
    });

    const mainColumnMaxWidth = style("mainColumnMaxWidth", {
        ...{
            "&.hasAdjacentPanel": {
                flexBasis: calc(`100% - ${styleUnit(vars.panel.paddedWidth)}`),
                maxWidth: calc(`100% - ${styleUnit(vars.panel.paddedWidth)}`),

                ...mediaQueries({
                    [SectionTypes.THREE_COLUMNS]: {
                        oneColumnDown: {
                            flexBasis: percent(100),
                            maxWidth: percent(100),
                        },
                    },
                }),
            },
            "&.hasTwoAdjacentPanels": {
                flexBasis: calc(`100% - ${styleUnit(vars.panel.paddedWidth * 2)}`),
                maxWidth: calc(`100% - ${styleUnit(vars.panel.paddedWidth * 2)}`),
                ...mediaQueries({
                    [SectionTypes.THREE_COLUMNS]: {
                        oneColumnDown: {
                            flexBasis: percent(100),
                            maxWidth: percent(100),
                        },
                    },
                }),
            },
        },
    });

    const breadcrumbs = style("breadcrumbs", {});

    const isSticky = style("isSticky", {
        ...sticky(),
        height: percent(100),
        ...mediaQueries({
            [SectionTypes.THREE_COLUMNS]: {
                oneColumnDown: {
                    position: "relative",
                    top: "auto",
                    left: "auto",
                    bottom: "auto",
                },
            },
        }),
    });

    // To remove when we have overlay styles converted
    cssRule(`.overlay .${root}.noBreadcrumbs .${main}`, {
        paddingTop: 0,
    });

    const breadcrumbsContainer = style("breadcrumbs", {
        paddingBottom: styleUnit(14),
    });

    const layoutSpecificStyles = (style) => {
        const middleColumnMaxWidth = style("middleColumnMaxWidth", {
            ...{
                "&.hasAdjacentPanel": {
                    flexBasis: calc(`100% - ${styleUnit(vars.panel.paddedWidth)}`),
                    maxWidth: calc(`100% - ${styleUnit(vars.panel.paddedWidth)}`),
                    ...mediaQueries({
                        [SectionTypes.THREE_COLUMNS]: {
                            oneColumnDown: {
                                flexBasis: percent(100),
                                maxWidth: percent(100),
                            },
                        },
                    }),
                },
                "&.hasTwoAdjacentPanels": {
                    flexBasis: calc(`100% - ${styleUnit(vars.panel.paddedWidth * 2)}`),
                    maxWidth: calc(`100% - ${styleUnit(vars.panel.paddedWidth * 2)}`),
                    ...mediaQueries({
                        [SectionTypes.THREE_COLUMNS]: {
                            oneColumnDown: {
                                flexBasis: percent(100),
                                maxWidth: percent(100),
                            },
                        },
                    }),
                },
            },
        });

        const leftColumn = style("leftColumn", {
            position: "relative",
            width: styleUnit(vars.panel.paddedWidth),
            flexBasis: styleUnit(vars.panel.paddedWidth),
            minWidth: styleUnit(vars.panel.paddedWidth),
            paddingRight: offset ? styleUnit(offset) : undefined,
        });

        const rightColumn = style("rightColumn", {
            position: "relative",
            width: styleUnit(vars.panel.paddedWidth),
            flexBasis: styleUnit(vars.panel.paddedWidth),
            minWidth: styleUnit(vars.panel.paddedWidth),
            overflow: "initial",
            paddingRight: offset ? styleUnit(offset) : undefined,
        });

        return {
            leftColumn,
            rightColumn,
            middleColumnMaxWidth,
        };
    };

    const mainPanelWidgetMixin: CSSObject = {
        ...Mixins.margin({
            vertical: globalVars.spacer.pageComponentCompact,
        }),
        "&:first-child": {
            marginTop: 0,
        },
        "&:last-child": {
            marginBottom: 0,
        },
    };
    const mainPanelWidget = css(mainPanelWidgetMixin);

    const secondaryPanelWidgetMixin: CSSObject = {
        ...Mixins.margin({
            vertical: globalVars.spacer.panelComponent,
        }),
        "&:first-child": {
            marginTop: 0,
        },
        "&:last-child": {
            marginBottom: 0,
        },
    };
    const secondaryPanelWidget = css(secondaryPanelWidgetMixin);

    const mainPanelHeadingBlock = css({
        marginBottom: globalVars.spacer.headingBoxCompact,
    });

    const secondaryPanelHeadingBlock = css({
        marginBottom: 0,
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
        mainColumn,
        mainColumnMaxWidth,
        panel,
        isSticky,
        breadcrumbs,
        breadcrumbsContainer,
        layoutSpecificStyles,
        mainPanelWidgetMixin,
        mainPanelWidget,
        secondaryPanelWidgetMixin,
        secondaryPanelWidget,
        mainPanelHeadingBlock,
        secondaryPanelHeadingBlock,
    };
};
