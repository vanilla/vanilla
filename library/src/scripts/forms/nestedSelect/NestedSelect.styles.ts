/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { Mixins } from "@library/styles/Mixins";
import { globalVariables } from "@library/styles/globalStyleVars";
import { placeholderStyles } from "@library/styles/styleHelpers";
import { useThemeCache } from "@library/styles/styleUtils";
import { formElementsVariables } from "../formElementStyles";
import { inputVariables } from "../inputStyles";

const DEFAULT_PARAMS = {
    compact: false,
    maxHeight: 250,
};
export const nestedSelectClasses = useThemeCache((params: { compact?: boolean; maxHeight?: number } = {}) => {
    const { compact = false, maxHeight = 250 } = params;
    const globalVars = globalVariables();
    const inputVars = inputVariables();
    const formVars = formElementsVariables();
    const fontSize = compact ? globalVars.fonts.size.medium : globalVars.fonts.size.large;

    const root = css({
        position: "relative",
    });

    const label = css({});

    const clearButton = css({
        ...Mixins.font({
            ...globalVars.fontSizeAndWeightVars("small", "bold"),
        }),
    });

    const inputContainer = css({
        display: "flex",
        flexDirection: "row",
        alignItems: "flex-start",
        justifyContent: "space-between",
        position: "relative",
        height: "auto",
        ...(compact && {
            minHeight: 28,
        }),
    });

    const input = css({
        display: "flex",
        flexDirection: "row",
        alignItems: "center",
        justifyContent: "flex-start",
        flexWrap: "wrap",
        "&& input": {
            minWidth: 100,
            flex: 1,
            fontSize,
            lineHeight: 1,
            ...placeholderStyles({
                color: ColorsUtils.colorOut(inputVars.colors.fg.mix(inputVars.colors.bg, 0.65)),
            }),
            ...(compact && {
                height: 28,
                minHeight: 28,
                ...Mixins.padding({ vertical: 0 }),
            }),
        },
    });

    const inputError = css({
        borderColor: ColorsUtils.colorOut(globalVars.messageColors.error.fg),
    });

    const inputIcon = css({
        height: 36,
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        ...Mixins.padding({ horizontal: 8 }),
        ...(compact && {
            height: 28,
        }),
    });

    const token = css({
        ...Mixins.margin({
            horizontal: formVars.spacing.horizontalPadding / 2,
            vertical: formVars.spacing.verticalPadding,
        }),
    });

    const itemPadding = Mixins.padding({
        vertical: compact ? 6 : 8,
        horizontal: compact ? 8 : 12,
    });

    const menu = css({
        background: ColorsUtils.colorOut(inputVars.colors.bg),
        ...Mixins.margin({ bottom: 16 }),
        padding: 0,
        ...Mixins.border(inputVars.border),
        listStyle: "none",
        maxHeight,
        position: "absolute",
        top: "calc(100% + 2px)",
        right: 0,
        left: 0,
        zIndex: 1050,
        overflow: "auto",
    });

    const menuItem = (depth: number) =>
        css({
            ...itemPadding,
            paddingInlineStart: `calc(8px + ${depth * 2}ch)`,
            fontSize,
            margin: 0,
            cursor: "pointer",
            display: "flex",
            flexDirection: "row",
            alignItems: "center",
            justifyContent: "space-between",
            gap: itemPadding.paddingInline,
            "&:hover, &.highlighted": {
                background: ColorsUtils.colorOut(inputVars.colors.bg.mix(globalVars.mainColors.primary, 0.9)),
            },
        });

    const menuItemLabel = css({
        flex: 1,
        display: "flex",
        flexDirection: "column",
        gap: 2,
    });

    const menuItemGroup = css({
        textTransform: "uppercase",
        color: ColorsUtils.colorOut(inputVars.colors.fg.mix(inputVars.colors.bg, 0.75)),
        fontSize: fontSize - 4,
    });

    const menuItemSelected = css({
        background: ColorsUtils.colorOut(inputVars.colors.bg.mix(globalVars.mainColors.primary, 0.9)),
    });

    const menuItemSelectedIcon = css({
        color: ColorsUtils.colorOut(globalVars.mainColors.primary),
        height: "1.125em",
    });

    const menuSeparator = css({
        height: 1,
        ...Mixins.margin({ vertical: 8 }),
        background: ColorsUtils.colorOut(inputVars.border.color),
        "&:last-of-type, &:first-of-type": {
            height: 0,
        },
    });

    const menuHeader = (isRoot: boolean, depth: number) =>
        css({
            textAlign: isRoot ? "center" : "left",
            ...itemPadding,
            paddingInlineStart: `calc(8px + (${depth * 2}ch))`,
            textTransform: "uppercase",
            fontWeight: 600,
            color: ColorsUtils.colorOut(inputVars.colors.fg.mix(inputVars.colors.bg, 0.75)),
            fontSize: fontSize - 2,
        });

    const menuNoOption = css({
        ...itemPadding,
        textAlign: "center",
        color: ColorsUtils.colorOut(inputVars.colors.fg.mix(inputVars.colors.bg, 0.6)),
        fontStyle: "italic",
    });

    return {
        root,
        label,
        clearButton,
        inputContainer,
        input,
        inputError,
        inputIcon,
        token,
        menu,
        menuItem,
        menuItemLabel,
        menuItemGroup,
        menuItemSelected,
        menuItemSelectedIcon,
        menuSeparator,
        menuHeader,
        menuNoOption,
    };
});
