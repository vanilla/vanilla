/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { userSelect } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { Mixins } from "@library/styles/Mixins";
import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";

export const navigationLinkItemVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("navigationManager");

    const dragging = makeThemeVars("dragging", {
        lineHeight: 18,
        fontWeight: globalVars.fonts.weights.bold,
        border: {
            radius: 2,
            color: globalVars.mixBgAndFg(0.2),
        },
        scrollGutter: {
            mobile: globalVars.gutter.size * 2,
        },
    });

    const states = makeThemeVars("states", {
        hover: {
            bg: globalVars.mainColors.primary.fade(0.1),
        },
        active: {
            bg: globalVars.mainColors.primary.fade(0.1),
        },
        dragged: {
            bg: globalVars.mainColors.bg,
            shadow: `0 5px 10px 0 rgba(0, 0, 0, 0.3)`,
        },
    });

    return {
        dragging,
        states,
    };
});

export default useThemeCache(() => {
    const vars = navigationLinkItemVariables();
    const globalVars = globalVariables();
    const style = styleFactory("navigationManager");

    const nameColumn = style("nameColumn", {
        flexGrow: 0,
        flexShrink: 0,
        flexBasis: 224,
        marginLeft: -6,
        marginRight: 6,
        ...Mixins.padding({
            horizontal: 2,
        }),
    });

    const urlColumn = style("urlColumn", {
        flexGrow: 0,
        flexShrink: 0,
        flexBasis: 224,
        marginLeft: -6,
        marginRight: 6,
        ...Mixins.padding({
            horizontal: 2,
        }),
    });

    const spacer = style("spacer", {
        flex: 1,
    });

    const actions = style("actions", {
        flexGrow: 0,
        flexShrink: 0,
        flexBasis: 160,
        visibility: "hidden",
        display: "flex",
        justifyContent: "flex-end",
        paddingRight: 4,
    });

    const expandCollapseButton = style("expandCollapseButton", {
        ...Mixins.padding({ horizontal: 12 }),
    });

    const cancelButton = style("cancelButton", {
        ...Mixins.margin({ horizontal: 8 }),
    });

    const applyButton = style("applyButton", {
        ...Mixins.margin({ horizontal: 8 }),
    });

    const showButton = style("showButton", {
        color: ColorsUtils.colorOut(globalVars.mainColors.fg),
        ...Mixins.margin({ horizontal: 8 }),
    });

    const editButton = style("editButton", {
        ...Mixins.margin({ horizontal: 8 }),
    });

    const deleteButton = style("deleteButton", {
        color: ColorsUtils.colorOut(globalVars.messageColors.deleted.bg),
        ...Mixins.margin({ horizontal: 8 }),
    });

    const editableInput = style("editableInput", {
        ...Mixins.border({
            color: globalVars.mixBgAndFg(0.2),
            radius: 0,
            width: 1,
        }),
        ...Mixins.padding({
            vertical: 0,
            horizontal: 5,
        }),
        ...Mixins.margin({
            vertical: -1,
            left: -6,
        }),
        lineHeight: styleUnit(22),
        outline: 0,
        ...{
            "&:focus": {
                ...Mixins.border({
                    color: globalVars.mainColors.primary.fade(0.5),
                    radius: 0,
                    width: 1,
                }),
            },
        },
    });

    const container = style("container", {
        display: "flex",
        alignItems: "center",
        minHeight: 28,
        ...userSelect("none"),
        ...{
            [`&:hover .${actions}, &.isEditing .${actions}`]: {
                visibility: "visible",
            },
            "&&&.isDragging": {
                minWidth: styleUnit(300),
                opacity: 0.65,
                boxShadow: vars.states.dragged.shadow,
                ...Mixins.border(vars.dragging.border),
                fontWeight: vars.dragging.fontWeight,
                backgroundColor: ColorsUtils.colorOut(vars.states.dragged.bg),
            },
            "&.isHiddenItem": {
                color: ColorsUtils.colorOut(globalVars.mainColors.fg.fade(0.5)),
            },
            "&.hasChildren": {
                ...Mixins.font({
                    weight: globalVars.fonts.weights.bold,
                }),
            },
            "&:hover, &&.isCombining": {
                backgroundColor: ColorsUtils.colorOut(vars.states.hover.bg),
            },
            "&:focus-within, &.isEditing": {
                backgroundColor: ColorsUtils.colorOut(vars.states.active.bg),
            },
            "&.hasError": {
                color: ColorsUtils.colorOut(globalVars.messageColors.error.fg),
            },
        },
    });

    return {
        container,
        expandCollapseButton,
        nameColumn,
        urlColumn,
        editableInput,
        spacer,
        cancelButton,
        applyButton,
        editButton,
        showButton,
        deleteButton,
        actions,
    };
});
