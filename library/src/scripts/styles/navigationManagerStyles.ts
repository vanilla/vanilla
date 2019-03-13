/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { borders, colorOut, margins, paddings, unit } from "@library/styles/styleHelpers";
import { important, percent } from "csx";
import { shadowHelper } from "@library/styles/shadowHelpers";
import { OutlineProperty } from "csstype";

export const navigationManagerVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("navigationManager");

    const colors = makeThemeVars("colors", {
        fg: globalVars.mainColors.primary,
    });

    const dragging = makeThemeVars("dragging", {
        lineHeight: 18,
        border: {
            radius: 2,
            color: globalVars.mixBgAndFg(0.9),
        },
        bg: globalVars.mixPrimaryAndBg(0.2),
    });

    const error = makeThemeVars("error", {
        color: globalVars.feedbackColors.error,
    });

    const item = makeThemeVars("item", {
        height: 28,
    });

    const deleteButton = makeThemeVars("deleteButton", {
        color: globalVars.feedbackColors.error,
    });

    const actionButton = makeThemeVars("actionButton", {
        shadowColor: globalVars.mainColors.bg,
    });

    const renameButton = makeThemeVars("renameButton", {
        disabled: {
            fg: globalVars.mixBgAndFg(0.7),
        },
    });

    const folderIcon = makeThemeVars("folderIcon", {
        width: 19,
        height: 14,
        fg: globalVars.mixBgAndFg(0.5),
    });

    const chevron = makeThemeVars("chevron", {
        margin: 8,
        width: 8,
    });

    const input = makeThemeVars("input", {
        padding: 6,
        lineHeight: 24,
    });

    return {
        colors,
        dragging,
        error,
        item,
        deleteButton,
        actionButton,
        renameButton,
        folderIcon,
        chevron,
        input,
    };
});

export const navigationManagerClasses = useThemeCache(() => {
    const vars = navigationManagerVariables();
    const globalVars = globalVariables();
    const style = styleFactory("navigationManager");
    const chevronFullWidth = vars.chevron.width + 2 * vars.chevron.margin;
    const buttonWidth = chevronFullWidth + vars.folderIcon.width;
    const shadows = shadowHelper();

    const root = style({
        $nest: {
            "& > [data-react-beautiful-dnd-droppable]": {
                paddingBottom: unit(50),
                marginLeft: unit(-chevronFullWidth),
            },
        },
    });

    const container = style("container", {
        position: "relative",
        maxWidth: unit(800),
        width: percent(100),
        margin: "auto",
    });

    const header = style("header", {
        ...paddings({
            left: unit(globalVars.gutter.half),
            right: unit(globalVars.gutter.half),
        }),
    });

    const item = style("item", {
        maxWidth: percent(100),
        border: important(0),
        $nest: {
            "&.isDragging": {
                minWidth: unit(300),
                $nest: {
                    "& .navigationManager-draggable": {
                        ...shadows.embed(),
                        paddingRight: globalVars.fonts.weights.semiBold,
                        ...borders(vars.dragging.border),
                        backgroundColor: colorOut(globalVars.mainColors.bg),
                    },
                    "& .navigationManager-action": {
                        display: important("none"),
                    },
                },
            },
            "&:hover .navigationManager-draggable": {
                backgroundColor: colorOut(vars.dragging.bg.fade(0.4)),
            },

            "&.isActive .navigationManager-draggable": {
                backgroundColor: colorOut(vars.dragging.bg),
                ...borders(vars.dragging.border),
            },
            "&.isActive .navigationManager-action": {
                display: "block",
            },

            "&:focus .navigationManager-draggable": {
                backgroundColor: colorOut(vars.dragging.bg),
                ...borders(vars.dragging.border),
            },
            "&:focus .navigationManager-action": {
                display: "block",
            },

            "&.hasError .navigationManager-itemLabel": {
                color: colorOut(globalVars.feedbackColors.error.fg),
            },
        },
    });

    const draggable = style("draggable", {
        position: "relative",
        display: "flex",
        alignItems: "center",
        justifyContent: "flex-start",
        minHeight: unit(vars.item.height),
        lineHeight: unit(vars.dragging.lineHeight),
        fontSize: unit(globalVars.fonts.size.medium),
        backgroundColor: colorOut(globalVars.mainColors.bg),
        ...borders({
            color: "transparent",
        }),
        marginLeft: unit(18),
    });

    const action = style("action", {
        fontSize: unit(globalVars.fonts.size.medium),
        fontWeight: globalVars.fonts.weights.bold,
        minHeight: unit(vars.item.height),
        ...paddings({
            top: 0,
            right: 9,
            bottom: 0,
            left: 9,
        }),
        display: "none",
        textShadow: `${colorOut(vars.actionButton.shadowColor)} 0 0 2px`,
        whiteSpace: "nowrap",
        ...borders({
            color: "transparent",
        }),
        $nest: {
            "&:focus": {
                ...borders({
                    color: globalVars.mainColors.primary.fade(0.5),
                }),
            },
        },
    });

    const deleteItem = style("deleteItem", {
        color: colorOut(vars.deleteButton.color.fg),
    });

    const submitRename = style("submitRename", {
        $nest: {
            "&[disabled]": {
                cursor: "not-allowed",
                color: colorOut(vars.renameButton.disabled.fg),
            },
        },
    });
    const input = style("input", {
        ...borders({
            color: "transparent",
        }),
        ...paddings({
            top: 0,
            right: vars.input.padding,
            bottom: 0,
            left: vars.input.padding,
        }),
        lineHeight: unit(vars.input.lineHeight),
        marginLeft: unit(-1),
        $nest: {
            "&.isFolder": {
                fontWeight: globalVars.fonts.weights.semiBold,
            },
            "&:focus": {
                borderColor: colorOut(globalVars.mainColors.primary.fade(0.5)),
            },
        },
    });
    const itemLabel = style("itemLabel", {
        flexGrow: 1,
        ...paddings({
            top: unit(3),
            right: unit(vars.input.padding),
            bottom: unit(3),
            left: unit(vars.input.padding),
        }),
        $nest: {
            "&.isFolder": {
                fontWeight: globalVars.fonts.weights.semiBold,
            },
        },
    });

    const itemIcon = style("itemIcon", {
        color: "inherit",
    });

    const articleIcon = style("articleIcon", {
        display: "block",
        margin: "auto",
    });

    const articleIconFill = style("articleIconFill", {
        fill: colorOut(globalVars.mainColors.bg),
    });

    const toggleFolder = style("toggleFolder", {
        display: "flex",
        position: "relative",
        alignItems: "center",
        padding: 0,
        height: unit(vars.item.height),
        width: unit(buttonWidth),
        flexBasis: unit(buttonWidth),
    });

    const articlePage = style("articlePage", {
        display: "flex",
        alignItems: "center",
        width: unit(vars.item.height),
        minHeight: unit(vars.item.height),
        marginLeft: unit(18),
    });

    const toggleSpacer = style("toggleSpacer", {
        height: unit(globalVars.icon.sizes.default),
        width: unit(chevronFullWidth),
    });

    const triangle = style("triangle", {
        width: unit(vars.chevron.width),
        ...margins({
            top: 0,
            right: vars.chevron.width,
            bottom: 0,
            left: vars.chevron.width,
        }),
    });

    const folder = style("folder", {
        display: "block",
        color: colorOut(vars.folderIcon.fg),
        margin: "auto",
    });

    const folderIcon = style("folderIcon", {
        width: unit(vars.folderIcon.width),
        height: unit(vars.folderIcon.height),
    });

    const editMode = style("editMode", {
        display: "flex",
        alignItems: "center",
        flexGrow: 1,
    });

    const text = style("text", {
        flexGrow: 1,
    });

    const noBorder = style("noBorder", {
        border: important(0),
    });

    return {
        root,
        container,
        header,
        item,
        draggable,
        action,
        submitRename,
        deleteItem,
        input,
        itemLabel,
        itemIcon,
        articleIcon,
        articleIconFill,
        toggleFolder,
        articlePage,
        toggleSpacer,
        triangle,
        folder,
        folderIcon,
        editMode,
        text,
        noBorder,
    };
});
