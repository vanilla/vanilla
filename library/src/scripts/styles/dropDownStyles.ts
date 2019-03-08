/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { layoutVariables } from "@library/styles/layoutStyles";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { shadowHelper } from "@library/styles/shadowHelpers";
import { borders, unit, paddings, states, font, userSelect, margins, colorOut } from "@library/styles/styleHelpers";
import get from "lodash/get";
import { allLinkStates } from "@library/styles/styleHelpers";
import { percent } from "csx";
import { states } from "@library/styles/styleHelpers";
import { Layout } from "log4js";
import PanelLayout from "@library/components/layouts/PanelLayout";

export const dropDownVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("dropDown");

    const sizing = makeThemeVars("sizing", {
        width: 250,
        minHeight: 600,
    });

    const spacer = makeThemeVars("spacer", {
        margin: 6,
    });

    // Defaults to globals, but here in case we want to overwrite it
    const border = makeThemeVars("border", {});

    const metas = makeThemeVars("metas", {
        font: {
            size: globalVars.meta.text.fontSize,
            color: globalVars.meta.text.color,
        },
        padding: {
            top: 6,
            right: 18,
            bottom: 6,
            left: 18,
        },
    });

    const item = makeThemeVars("item", {
        minHeight: 30,
        mobile: {
            minHeight: 44,
            fontSize: 16,
        },
        padding: {
            top: 4,
            bottom: 4,
        },
    });

    const sectionTitle = makeThemeVars("sectionTitle", {
        padding: {
            top: 6,
            bottom: 6,
        },
    });

    const title = makeThemeVars("title", {
        color: globalVars.mixBgAndFg(0.15),
    });

    const contents = makeThemeVars("contents", {
        bg: globalVars.mainColors.bg,
        fg: globalVars.mainColors.fg,
    });

    return {
        sizing,
        border,
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
    const layoutVars = layoutVariables();
    const style = styleFactory("dropDown");
    const shadows = shadowHelper();
    const mediaQueries = layoutVariables().mediaQueries();

    const root = style({
        position: "relative",
    });

    const paddedList = style("paddedList", {
        paddingTop: layoutVars.gutter.quarterSize,
        paddingBottom: layoutVars.gutter.quarterSize,
    });

    const contents = style("contents", {
        ...shadows.dropDown,
        position: "absolute",
        minWidth: unit(vars.sizing.width),
        backgroundColor: colorOut(vars.contents.bg),
        color: colorOut(vars.contents.fg),
        overflow: "hidden",
        ...borders(get(vars, "border", {})),
        zIndex: 1,
        $nest: {
            "&.isParentWidth": {
                minWidth: "initial",
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
                    top: 12,
                    bottom: 12,
                }),
            },
            "&:empty": {
                display: "none",
            },
        },
    });

    const asModal = style("asModal", {
        $nest: {
            "&.hasVerticalPadding": {
                ...paddings({
                    top: 12,
                    bottom: 12,
                }),
            },
        },
    });

    const likeDropDownContent = style("likeDropDownContent", {
        ...shadows.dropDown(),
        backgroundColor: colorOut(globalVars.mainColors.bg),
        ...borders(),
    });

    const items = style("items", {
        fontSize: unit(globalVars.fonts.size.medium),
    });

    const metaItems = style("metaItems", {
        ...paddings(vars.metas.padding),
        $nest: {
            "&.dropDown-item": {
                display: "block",
                ...font(vars.metas.font),
                ...paddings(vars.metas.padding),
            },
            $nest: {
                "& + .dropDown-metaItem": {
                    paddingTop: unit(vars.metas.padding.top),
                },
            },
        },
    });

    const meta = style("meta", {
        display: "block",
    });

    // const metaLink = style("metaLink", {
    //     ...allLinkStates({
    //         color: globalVars.links.colors.default,
    //     }),
    // });

    const item = style("item", {
        display: "flex",
        alignItems: "center",
        justifyContent: "flex-start",
        width: percent(100),
    });

    const section = style("section", {
        display: "block",
    });

    const toggleButtonIcon = style("toggleButtonIcon", {
        $nest: {
            ...states({
                color: colorOut(globalVars.mainColors.primary),
            }),
        },
    });

    // Replaces: .dropDownItem-button, .dropDownItem-link
    const action = style(
        "action",
        {
            appearance: "none",
            display: "flex",
            alignItems: "center",
            width: percent(100),
            textAlign: "left",
            color: "inherit",
            minHeight: unit(vars.item.minHeight),
            lineHeight: unit(globalVars.lineHeights.condensed),
            ...paddings(vars.item.padding),
            ...userSelect("none"),
            $nest: {
                ...states({
                    backgroundColor: colorOut(globalVars.states.active.color),
                }),
            },
        },
        mediaQueries.oneColumn({
            fontSize: unit(vars.item.mobile.fontSize),
            fontWeight: globalVars.fonts.weights.semiBold,
            minHeight: unit(vars.item.mobile.minHeight),
        }),
    );

    const text = style("text", {
        display: "block",
    });

    const separator = style("separator", {
        height: unit(globalVars.separator.size),
        backgroundColor: colorOut(globalVars.separator.color),
        ...margins({
            top: vars.spacer.margin,
            bottom: vars.spacer.margin,
        }),
    });

    const sectionHeading = style("sectionHeading", {
        color: colorOut(globalVars.meta.text.color),
        fontSize: unit(globalVars.fonts.size.small),
        textTransform: "uppercase",
        textAlign: "center",
        fontWeight: unit(globalVars.fonts.weights.semiBold),
        ...paddings(vars.sectionTitle.padding),
    });

    const sectionContents = style("sectionContents", {
        display: "block",
    });

    const count = style("count", {
        fontSize: unit(globalVars.fonts.size.small),
        paddingLeft: "1em",
        marginLeft: "auto",
    });

    const verticalPadding = style("verticalPadding", {
        ...paddings({
            top: vars.spacer.margin,
            right: 0,
            bottom: vars.spacer.margin,
            left: 0,
        }),
    });

    const title = style("title", {
        ...font({
            weight: globalVars.fonts.weights.semiBold,
            size: globalVars.fonts.size.small,
            lineHeight: globalVars.lineHeights.condensed,
        }),
        ...paddings({
            top: 0,
            right: 0,
            bottom: 0,
            left: vars.spacer.margin,
        }),
        textTransform: "uppercase",
        color: colorOut(vars.title.color),
    });

    return {
        root,
        paddedList,
        contents,
        asModal,
        likeDropDownContent,
        items,
        metaItems,
        meta,
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
    };
});
