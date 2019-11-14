/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import {
    borders,
    colorOut,
    fonts,
    margins,
    paddings,
    buttonStates,
    unit,
    userSelect,
    negative,
} from "@library/styles/styleHelpers";
import { shadowHelper, shadowOrBorderBasedOnLightness } from "@library/styles/shadowHelpers";
import { NestedCSSProperties, TLength } from "typestyle/lib/types";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { important, percent } from "csx";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { buttonResetMixin } from "@library/forms/buttonStyles";

export const notUserContent = "u-notUserContent";

export const dropDownVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("dropDown");

    const sizing = makeThemeVars("sizing", {
        widths: {
            default: 250,
            medium: 350,
        },
        minHeight: 600,
    });

    const spacer = makeThemeVars("spacer", {
        margin: {
            vertical: 8,
        },
    });

    const metas = makeThemeVars("metas", {
        font: {
            size: globalVars.meta.text.fontSize,
            color: globalVars.meta.text.color,
        },
        padding: {
            vertical: 6,
            horizontal: 14,
        },
    });

    const item = makeThemeVars("item", {
        colors: {
            fg: globalVars.mainColors.fg,
        },
        minHeight: 30,
        mobile: {
            minHeight: 44,
            fontSize: 16,
        },

        padding: {
            top: 6,
            horizontal: 14,
        },
    });

    const sectionTitle = makeThemeVars("sectionTitle", {
        padding: {
            top: 6,
            bottom: 6,
        },
    });

    const title = makeThemeVars("title", {
        color: globalVars.mainColors.fg,
    });

    const contents = makeThemeVars("contents", {
        bg: globalVars.mainColors.bg,
        fg: globalVars.mainColors.fg,
        border: {
            radius: globalVars.border.radius,
            color: globalVars.border.color,
        },
        padding: {
            vertical: 9,
            horizontal: 16,
        },
    });

    return {
        sizing,
        metas,
        item,
        sectionTitle,
        spacer,
        title,
        contents,
    };
});

export const dropDownClasses = useThemeCache(() => {
    const vars = dropDownVariables();
    const globalVars = globalVariables();
    const style = styleFactory("dropDown");
    const shadows = shadowHelper();
    const mediaQueries = layoutVariables().mediaQueries();

    const root = style({
        position: "relative",
    });

    const contents = style("contents", {
        position: "absolute",
        minWidth: unit(vars.sizing.widths.default),
        backgroundColor: colorOut(vars.contents.bg),
        color: colorOut(vars.contents.fg),
        overflow: "auto",
        ...shadowOrBorderBasedOnLightness(vars.contents.bg, borders({}), shadows.dropDown()),
        ...borders(vars.contents.border),
        $nest: {
            "&&": {
                zIndex: 3,
            },
            "&.isMedium": {
                width: unit(vars.sizing.widths.medium),
            },
            "&.isParentWidth": {
                minWidth: "initial",
                left: 0,
                right: 0,
            },
            "&.isOwnWidth": {
                width: "initial",
            },
            "&.isRightAligned": {
                right: 0,
                top: 0,
            },
            "& .frame": {
                boxShadow: "none",
            },
            "&.noMinWidth": {
                minWidth: 0,
            },
            "&.hasVerticalPadding": {
                ...paddings({
                    vertical: 12,
                    horizontal: important(0),
                }),
            },
        },
    } as NestedCSSProperties);

    const asModal = style("asModal", {
        $nest: {
            "&.hasVerticalPadding": paddings({
                vertical: 12,
            }),
        },
    });

    const likeDropDownContent = style("likeDropDownContent", {
        ...shadows.dropDown(),
        backgroundColor: colorOut(globalVars.mainColors.bg),
        ...borders(),
    } as NestedCSSProperties);

    const items = style("items", {
        paddingTop: 3,
        paddingBottom: 3,
        paddingLeft: 0,
        paddingRight: 0,
        fontSize: unit(globalVars.fonts.size.medium),
    });

    const metaItems = style("metaItems", {
        $nest: {
            "&&": {
                display: "block",
            },
        },
        ...paddings(vars.metas.padding),
    });

    const metaItem = style("metaItem", {
        $nest: {
            "& + &": {
                paddingTop: unit(vars.item.padding.top),
            },
        },
        ...fonts(vars.metas.font),
    });

    // wrapping element
    const item = style("item", {
        ...userSelect("none"),
        display: "flex",
        alignItems: "center",
        justifyContent: "flex-start",
        width: percent(100),
        margin: 0,
        color: "inherit",
        textAlign: "left",
        lineHeight: globalVars.lineHeights.condensed,
    });

    const section = style("section", {
        display: "block",
    });

    const toggleButtonIcon = style("toggleButtonIcon", {
        $nest: {
            ...buttonStates({
                allStates: {
                    color: colorOut(globalVars.mainColors.primary),
                },
            }),
        },
    });

    // Contents (button or link)
    // Replaces: .dropDownItem-button, .dropDownItem-link
    const action = style("action", {
        $nest: {
            "&&": {
                ...buttonResetMixin(),
                cursor: "pointer",
                appearance: "none",
                display: "flex",
                alignItems: "center",
                width: percent(100),
                textAlign: "left",
                minHeight: unit(vars.item.minHeight),
                lineHeight: unit(globalVars.lineHeights.condensed),
                ...paddings({
                    vertical: 4,
                    horizontal: vars.item.padding.horizontal,
                }),
                ...borders({
                    color: "transparent",
                    radius: 0,
                }),
                color: colorOut(vars.item.colors.fg),
                ...userSelect("none"),
                ...buttonStates({
                    allStates: {
                        textShadow: "none",
                        outline: 0,
                    },
                    hover: {
                        backgroundColor: colorOut(globalVars.states.hover.color),
                    },
                    focus: {
                        backgroundColor: colorOut(globalVars.states.focus.color),
                    },
                    active: {
                        backgroundColor: colorOut(globalVars.states.active.color),
                    },
                    accessibleFocus: {
                        borderColor: colorOut(globalVars.mainColors.primary),
                    },
                }),
                ...mediaQueries.oneColumnDown({
                    fontSize: unit(vars.item.mobile.fontSize),
                    fontWeight: globalVars.fonts.weights.semiBold,
                    minHeight: unit(vars.item.mobile.minHeight),
                }),
            },
        },
    });

    const text = style("text", {
        display: "block",
    });

    const separator = style("separator", {
        listStyle: "none",
        height: unit(globalVars.separator.size),
        backgroundColor: colorOut(globalVars.separator.color),
        ...margins(vars.spacer.margin),
    });

    const sectionHeading = style("sectionHeading", {
        color: colorOut(globalVars.meta.text.color),
        fontSize: unit(globalVars.fonts.size.small),
        textTransform: "uppercase",
        textAlign: "center",
        fontWeight: globalVars.fonts.weights.semiBold,
        ...(paddings(vars.sectionTitle.padding) as NestedCSSProperties),
    });

    const sectionContents = style("sectionContents", {
        display: "block",
    });

    const count = style("count", {
        fontSize: unit(globalVars.fonts.size.small),
        paddingLeft: "1em",
        marginLeft: "auto",
    });

    const verticalPadding = style(
        "verticalPadding",
        {
            ...paddings({
                vertical: vars.contents.padding.vertical,
                horizontal: 0,
            }),
        },
        mediaQueries.oneColumnDown({
            ...paddings({
                vertical: 0,
            }),
        }),
    );

    const noVerticalPadding = style("noVerticalPadding", {
        ...paddings({ vertical: 0 }),
    });

    const title = style("title", {
        ...fonts({
            weight: globalVars.fonts.weights.semiBold,
            size: globalVars.fonts.size.medium,
            lineHeight: globalVars.lineHeights.condensed,
        }),
        ...paddings({
            all: 0,
        }),
        ...margins({
            all: 0,
        }),
        textAlign: "left",
        flexGrow: 1,
        color: colorOut(vars.title.color),
    });

    const paddedFrame = style("paddedFrame", {
        ...paddings(vars.contents.padding),
    });

    const check = style("check", {
        color: colorOut(globalVars.mainColors.primary),
    });

    const flyoutOffset = vars.item.padding.horizontal + globalVars.border.width;

    const contentOffsetLeft = style("contentOffsetLeft", {
        transform: `translateX(${negative(unit(flyoutOffset))})`,
    });
    const contentOffsetRight = style("contentOffsetRight", {
        transform: `translateX(${unit(flyoutOffset)})`,
    });

    return {
        root,
        contents,
        asModal,
        likeDropDownContent,
        items,
        metaItems,
        metaItem,
        item,
        section,
        toggleButtonIcon,
        action,
        text,
        separator,
        sectionHeading,
        sectionContents,
        count,
        verticalPadding,
        title,
        noVerticalPadding,
        paddedFrame,
        check,
        contentOffsetLeft,
        contentOffsetRight,
    };
});
