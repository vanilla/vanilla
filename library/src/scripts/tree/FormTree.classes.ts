/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css, CSSObject } from "@emotion/css";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { userSelect } from "@library/styles/styleHelpersFeedback";
import { extendItemContainer } from "@library/styles/styleHelpersSpacing";
import { useThemeCache } from "@library/styles/themeCache";
import { formTreeVariables } from "@library/tree/FormTree.variables";

export const formTreeClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = formTreeVariables();

    const treeDescription = css({
        // Little bit of a cheat to get this to display as the mockups.
        display: "block",
        marginTop: -8,
    });

    const tree = css({
        position: "relative",
        ...Mixins.margin({
            bottom: 16,
        }),
    });
    const coloredRowBorder: CSSObject = {
        // Using a boxShadow here
        // border changes the sizing of the item.
        // outline doesn't support border radiuses.
        boxShadow: `inset 0 0 0 1px ${ColorsUtils.colorOut(globalVars.mainColors.statePrimary)}`,
        borderRadius: 2,
        outline: "none",
    };

    const dragShadow: CSSObject = {
        boxShadow: `0 5px 10px 0 rgba(0, 0, 0, 0.3)`,
    };

    const row = css({
        display: "flex",
        alignItems: "center",
        minHeight: vars.row.height,
        // Prevent text selection on the rows (they are draggable).
        ...userSelect("none"),

        // Rows extend out slightly from the edges of the container in the mockups
        ...extendItemContainer(4),
        ...Mixins.padding({ horizontal: 4 }),

        ...Mixins.padding({
            vertical: 2,
        }),

        "&:focus": {
            ...coloredRowBorder,
        },
    });

    const rowCompact = css({
        minHeight: vars.row.heightInCompact,
    });

    const rowActive = css({
        backgroundColor: ColorsUtils.colorOut(vars.row.activeBg),
    });

    const rowDragged = css({
        opacity: 0.7,
        ...coloredRowBorder,
        ...dragShadow,
        background: ColorsUtils.colorOut(globalVars.mainColors.bg),
        "&:focus": {
            // 2 shadows at work here.
            boxShadow: `${coloredRowBorder.boxShadow}, ${dragShadow.boxShadow}`,
        },
    });

    const iconSpace = 24;
    const rowIcon = css({});

    const rowIconWrapper = css({
        width: iconSpace,
        display: "flex",
        alignItems: "center",
    });

    const columnWrapperMixin: CSSObject = {
        flex: 1,
        display: "flex",
        alignItems: "center",
        paddingRight: 40,
    };

    const columnMixin: CSSObject = {
        width: "100%",
        height: "auto",
        maxWidth: 256,
    };

    const columnHeader = css({
        display: "flex",
        alignItems: "center",
        marginBottom: 4,
    });

    const columnLabel = css({
        ...columnMixin,
        fontWeight: globalVars.fonts.weights.bold,
    });

    const columnLabelWrapper = css({
        ...columnWrapperMixin,
        // "&:first-of-type": {
        // Offset over the rowIcon wrapper spacer.
        // Mockups have the label aligned to the icon
        // We can't just "omit" the icon spacer though because it is required
        // in order to keep spacing accurate on the flexboxes between the labels and rows.
        transform: `translateX(-${iconSpace}px)`,
        "& + &": {
            // Clear of the transform for items after the first one.
            // This is a bit more robust than :first-of-type (which only works on element type).
            transform: "none",
        },
    });

    const inputWrapper = css(columnWrapperMixin);

    const input = css({
        ...columnMixin,
        // Needed to match mockups.
        // These are much more compact than our normal inputs.
        lineHeight: "22px",

        // Make sure text of the input aligns with the labels.
        transform: "translateX(-2px)",

        ...Mixins.border({
            color: globalVars.mixBgAndFg(0.2),
            radius: 0,
            width: 1,
        }),
        ...Mixins.padding({
            vertical: 0,
            horizontal: 4,
        }),
        // These inputs appear as as text when disabled (generally when the row is no editable).
        "&:disabled": {
            opacity: 1,
            borderColor: "transparent",
            background: "transparent",
            textOverflow: "ellipsis",
        },
        ".isItemHidden &": {
            color: ColorsUtils.colorOut(globalVars.mainColors.fg.fade(0.5)),
        },
        "&:focus": {
            ...Mixins.border({
                color: globalVars.mainColors.primary.fade(0.5),
                radius: 0,
                width: 1,
            }),
        },
    });

    const autoComplete = css({
        ...columnMixin,
        borderColor: "transparent",
    });

    const actionWrapper = css({
        // Pretty rough size.
        // We can't use a dynamic width because we need to be able to match up the label widths.
        width: 160,
        display: "flex",
        alignItems: "center",
        justifyContent: "flex-end",
        paddingRight: 16,
        "& > *": {
            marginLeft: 16,
        },
    });

    const actionWrapperCompact = css({
        width: 60,
        paddingRight: 0,
        "& > *": {
            marginLeft: 0,
        },
    });

    const deleteHideAction = css({
        color: ColorsUtils.colorOut(globalVars.messageColors.deleted.bg, { makeImportant: true }),
    });

    return {
        tree,
        treeDescription,
        input,
        autoComplete,
        inputWrapper,
        row,
        rowCompact,
        rowActive,
        rowDragged,
        rowIcon,
        rowIconWrapper,
        actionWrapper,
        actionWrapperCompact,
        columnHeader,
        columnLabel,
        columnLabelWrapper,
        deleteHideAction,
    };
});
