/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { colorOut, paddings, states, unit } from "@library/styles/styleHelpers";
import { calc, percent } from "csx";

export const folderContentsVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("folderContents");

    const sizing = makeThemeVars("sizing", {
        minHeight: 396,
    });

    const colors = makeThemeVars("colors", {
        fg: globalVars.mainColors.primary,
    });

    const item = makeThemeVars("item", {
        spacing: 6,
        minHeight: 44,
    });

    const itemAction = makeThemeVars("itemAction", {
        width: 48,
    });

    const border = makeThemeVars("border", {
        color: globalVars.overlay.border.color,
    });

    return {
        sizing,
        colors,
        item,
        itemAction,
        border,
    };
});

export const folderContentsClasses = useThemeCache(() => {
    const vars = folderContentsVariables();
    const globalVars = globalVariables();
    const style = styleFactory("folderContents");

    const root = style({
        display: "block",
        position: "relative",
        minHeight: unit(vars.sizing.minHeight),
    });

    const items = style("items", {
        display: "block",
        position: "relative",
    });

    const item = style("item", {
        position: "relative",
        display: "flex",
        justifyContent: "flex-start",
        alignItems: "stretch",
        flexWrap: "nowrap",
        width: percent(100),
    });

    const folder = style("folder", {
        display: "block",
        width: percent(100),
        maxWidth: calc(`100% - ${unit(vars.itemAction.width)}`),
        cursor: "pointer",
    });

    const input = style("input", {
        $nest: {
            "&:focus": {
                $nest: {
                    "& + .folderContents-content": {
                        backgroundColor: colorOut(globalVars.states.hover.color),
                    },
                },
            },
        },
    });

    const content = style("content", {
        display: "flex",
        justifyContent: "center",
        alignItems: "center",
        flexWrap: "nowrap",
        flexGrow: 1,
        minHeight: unit(vars.item.minHeight),
        ...paddings({
            left: vars.item.spacing,
            right: vars.item.spacing,
        }),
        $nest: {
            ".isSelectable": {
                cursor: "pointer",
                $nest: {
                    "&:hover": {
                        backgroundColor: colorOut(globalVars.states.hover.color),
                    },
                },
            },
        },
    });

    const label = style("label", {
        flexGrow: 1,
        maxWidth: calc(`100% - ${unit(globalVars.icon.sizes.default + 4)}`),
    });

    const icon = style("icon", {
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        flexShrink: 1,
        marginRight: unit(4),
        width: unit(24),
    });

    const folderIcon = style("folderIcon", {
        color: colorOut(globalVars.mixBgAndFg(0.5)),
    });

    const checkIcon = style("checkIcon", {
        color: colorOut(globalVars.mainColors.primary),
    });

    const subFolder = style("subFolder", {
        position: "absolute",
        top: 0,
        right: 0,
        height: percent(100),
        minHeight: unit(vars.item.minHeight),
        width: unit(vars.itemAction.width),
        $nest: {
            ...states({
                allStates: {
                    backgroundColor: colorOut(globalVars.mainColors.primary),
                    color: colorOut(globalVars.mainColors.bg),
                },
            }),
        },
    });

    return {
        root,
        items,
        item,
        label,
        folder,
        input,
        content,
        icon,
        folderIcon,
        checkIcon,
        subFolder,
    };
});
