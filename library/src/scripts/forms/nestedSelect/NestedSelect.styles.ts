/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { Mixins } from "@library/styles/Mixins";
import { globalVariables } from "@library/styles/globalStyleVars";
import { placeholderStyles, singleBorder } from "@library/styles/styleHelpers";
import { getPixelNumber, useThemeCache } from "@library/styles/styleUtils";
import { formElementsVariables } from "../formElementStyles";
import { inputVariables } from "../inputStyles";
import { ColorVar } from "@library/styles/CssVar";

export const nestedSelectClasses = useThemeCache(
    (params: { compact?: boolean; inline?: boolean; maxHeight?: number } = {}) => {
        const { compact = false, inline = false, maxHeight = 250 } = params;
        const globalVars = globalVariables();
        const inputVars = inputVariables();
        const fontSize = inline ? "inherit" : compact ? 13 : globalVars.fonts.size.medium;

        const root = css({
            position: "relative",
            ...(inline
                ? {
                      display: "inline-flex",
                      alignItems: "center",
                      gap: 4,
                      width: "auto",
                      minWidth: "fit-content",
                      "&&": {
                          margin: 0,
                      },
                  }
                : {}),
        });

        const label = css({
            ...(inline
                ? {
                      width: "auto",
                      whiteSpace: "nowrap",
                      marginBottom: 0,
                  }
                : {}),
        });

        const clearButton = css({
            ...Mixins.margin({ top: 4, left: 2 }),
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
            ...(inline
                ? {
                      // Fighting input focus/hover border styles.
                      "&&&&": {
                          borderColor: "transparent",
                          "&.hasFocus": {
                              borderColor: ColorsUtils.var(ColorVar.InputBorderActive),
                          },
                      },
                  }
                : {}),
        });

        const input = css({
            display: "flex",
            flexDirection: "row",
            alignItems: "center",
            justifyContent: "flex-start",
            flexWrap: "wrap",
            width: "100%",
            position: "relative",

            "&& input": {
                minWidth: 100,
                flex: 1,
                fontSize,
                lineHeight: 1,
                ...placeholderStyles({
                    color: ColorsUtils.var(ColorVar.InputPlaceholder),
                }),
                ...(compact && {
                    height: 28,
                    minHeight: 28,
                    ...Mixins.padding({ vertical: 0 }),
                }),

                ...(inline
                    ? {
                          minWidth: 0,
                      }
                    : {}),
            },

            "&.tokens": {
                gap: 6,
                padding: 6,

                "& input": {
                    minHeight: 26,
                    paddingLeft: 0,
                    paddingTop: 0,
                    paddingBottom: 0,
                },
            },
        });

        const selectedValue = css({
            position: "absolute",
            top: "50%",
            left: 12,
            right: 32,
            transform: "translateY(-50%)",
            overflow: "hidden",
            textOverflow: "ellipsis",
            display: "inline-block",
            alignItems: "center",
            textWrap: "nowrap",
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

        const itemPadding = Mixins.padding({
            vertical: compact ? 6 : 8,
            horizontal: compact ? 8 : 12,
        });

        const menu = css({
            background: ColorsUtils.varOverride(ColorVar.DropdownBackground, inputVars.colors.bg),
            color: ColorsUtils.var(ColorVar.DropdownForeground, inputVars.colors.fg),
            ...Mixins.margin({ bottom: 16 }),
            padding: 0,
            ...Mixins.border(inputVars.border),
            borderColor: ColorsUtils.varOverride(ColorVar.InputBorder, inputVars.border.color),
            listStyle: "none",
            maxHeight,
            position: "absolute",
            top: "calc(100% + 2px)",
            right: 0,
            left: 0,
            zIndex: 1100,
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
                "&.highlighted": {
                    background: ColorsUtils.varOverride(
                        ColorVar.HighlightBackground,
                        inputVars.colors.bg.mix(globalVars.mainColors.primary, 0.9),
                    ),
                    color: ColorsUtils.varOverride(ColorVar.HighlightForeground, "inherit"),
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
            fontSize: fontSize ? getPixelNumber(fontSize) - 4 : undefined,
        });

        const menuItemSelected = css({});

        const menuItemSelectedIcon = css({
            color: ColorsUtils.var(ColorVar.Primary),
            height: "1.125em",
        });

        const menuSeparator = css({
            height: 1,
            ...Mixins.margin({ vertical: 8 }),
            background: ColorsUtils.varOverride(ColorVar.InputBorder, inputVars.border.color),
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
                fontSize: fontSize ? getPixelNumber(fontSize) - 2 : undefined,
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
            menu,
            menuItem,
            menuItemLabel,
            menuItemGroup,
            menuItemSelected,
            menuItemSelectedIcon,
            menuSeparator,
            menuHeader,
            menuNoOption,
            selectedValue,
        };
    },
);
