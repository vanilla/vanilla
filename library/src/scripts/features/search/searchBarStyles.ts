/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { formElementsVariables } from "@library/forms/formElementStyles";
import {
    absolutePosition,
    borderRadii,
    borders,
    colorOut,
    defaultTransition,
    EMPTY_BORDER,
    flexHelper,
    fonts,
    getVerticalPaddingForTextInput,
    IBorderRadiusValue,
    importantUnit,
    IStateColors,
    margins,
    paddings,
    singleBorder,
    unit,
    userSelect,
} from "@library/styles/styleHelpers";
import { calc, important, percent, px, translateX } from "csx";
import { titleBarVariables } from "@library/headers/titleBarStyles";
import { buttonGlobalVariables, buttonResetMixin } from "@library/forms/buttonStyles";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { shadowHelper } from "@library/styles/shadowHelpers";
import { inputBlockClasses } from "@library/forms/InputBlockStyles";
import { NestedCSSProperties } from "typestyle/lib/types";
import { inputVariables } from "@library/forms/inputStyles";
import { suggestedTextStyleHelper } from "@library/features/search/suggestedTextStyles";
import { searchResultsVariables } from "@library/features/search/searchResultsStyles";
import { paddingOffsetBasedOnBorderRadius } from "@library/forms/paddingOffsetFromBorderRadius";
import { IThemeVariables } from "@library/theming/themeReducer";
import { selectBoxClasses } from "@library/forms/select/selectBoxStyles";
import { SearchBarPresets } from "@library/banner/bannerStyles";

export const searchBarVariables = useThemeCache((forcedVars?: IThemeVariables) => {
    const globalVars = globalVariables(forcedVars);
    const formElementVars = formElementsVariables(forcedVars);
    const makeThemeVars = variableFactory("searchBar", forcedVars);

    const search = makeThemeVars("search", {
        minWidth: 109,
        fullBorderRadius: {
            extraHorizontalPadding: 10, // Padding when you have fully rounded border radius. Will be applied based on the amount of border radius. Set to "undefined" to turn off
        },
        compact: {
            minWidth: formElementVars.sizing.height,
        },
    });

    const placeholder = makeThemeVars("placeholder", {
        color: formElementVars.placeholder.color,
    });

    const heading = makeThemeVars("heading", {
        margin: 12,
    });

    const border = makeThemeVars("border", {
        ...EMPTY_BORDER,
        color: globalVars.border.color,
        width: globalVars.border.width,
        radius: globalVars.borderType.formElements.default.radius,
        inset: false, // indents border in to make sure we have contrast with background
    });

    const sizingInit = makeThemeVars("sizing", {
        height: formElementVars.sizing.height,
    });

    const sizing = makeThemeVars("sizing", {
        height: sizingInit.height,
        heightMinusBorder: sizingInit.height - border.width * 2,
    });

    const input = makeThemeVars("input", {
        fg: globalVars.mainColors.fg,
        bg: globalVars.mainColors.bg,
    });

    const searchIcon = makeThemeVars("searchIcon", {
        gap: 40,
        height: 13,
        width: 14,
        fg: input.fg.fade(0.7),
        padding: {
            right: 5,
        },
    });

    const results = makeThemeVars("results", {
        fg: globalVars.mainColors.fg,
        bg: globalVars.mainColors.bg,
        borderRadius: globalVars.border.radius,
    });

    const scope = makeThemeVars("scope", {
        width: 142,
        padding: 18,
        compact: {
            padding: 12,
            width: 58,
        },
    });

    const scopeIcon = makeThemeVars("scopeIcon", {
        width: 10,
        ratio: 6 / 10,
    });

    const stateColorsInit = makeThemeVars("stateColors", {
        allStates: globalVars.mainColors.primary,
        hoverOpacity: globalVars.constants.states.hover.borderEmphasis,
    });

    const stateColors: IStateColors = makeThemeVars("stateColors", {
        ...stateColorsInit,
        hover: stateColorsInit.allStates.fade(stateColorsInit.hoverOpacity), // This needs to be a mix and not an opacity so we can overlay the borders and not have the two borders mix together
        focus: stateColorsInit.allStates,
        active: stateColorsInit.allStates,
    });

    // Used when `SearchBarPresets.NO_BORDER` is active
    const noBorder = makeThemeVars("noBorder", {
        offset: 1,
    });

    // Used when `SearchBarPresets.BORDER` is active
    const withBorder = makeThemeVars("withBorder", {
        borderColor: globalVars.border.color,
    });

    const options = makeThemeVars("options", {
        compact: false,
        preset: SearchBarPresets.NO_BORDER,
    });

    return {
        search,
        noBorder,
        withBorder,
        searchIcon,
        sizing,
        placeholder,
        input,
        heading,
        results,
        border,
        scope,
        scopeIcon,
        stateColors,
        options,
    };
});

export interface ISearchBarOverwrites {
    borderRadius?: IBorderRadiusValue;
    compact?: boolean;
    preset?: SearchBarPresets;
}

export const searchBarClasses = useThemeCache((overwrites?: ISearchBarOverwrites) => {
    const style = styleFactory("searchBar");
    const shadow = shadowHelper();
    const classesInputBlock = inputBlockClasses();
    const vars = searchBarVariables();
    const { compact = vars.options.compact, borderRadius = vars.border.radius, preset = vars.options.preset } =
        overwrites || {};
    const globalVars = globalVariables();
    const layoutVars = layoutVariables();
    const inputVars = inputVariables();
    const titleBarVars = titleBarVariables();
    const formElementVars = formElementsVariables();
    const scopeMinWidth = compact ? vars.search.compact.minWidth : vars.search.minWidth;
    const mediaQueries = layoutVars.mediaQueries();
    const borderStyle = overwrites && overwrites.preset ? overwrites.preset : vars.options.preset;

    const isOuterBordered = borderStyle === SearchBarPresets.BORDER;
    const isInsetBordered = borderStyle === SearchBarPresets.NO_BORDER;

    const borderColor = isInsetBordered ? vars.input.bg : vars.border.color;

    const independentRoot = style("independentRoot", {
        display: "block",
        height: percent(100),
    });

    const verticalPadding = getVerticalPaddingForTextInput(
        vars.sizing.height,
        inputVars.font.size,
        formElementVars.border.width * 2,
    );
    const calculatedHeight = vars.sizing.height - verticalPadding * 2 - formElementVars.border.width * 2;

    const borderVars = vars.border || globalVars.border;

    const paddingOffset = paddingOffsetBasedOnBorderRadius({
        radius: borderRadius,
        extraPadding: vars.search.fullBorderRadius.extraHorizontalPadding,
        height: vars.sizing.height,
        side: "right",
    });

    const root = style({
        cursor: "pointer",
        $nest: {
            "& .searchBar__placeholder": {
                color: colorOut(vars.placeholder.color),
                margin: "auto",
                height: unit(calculatedHeight),
                lineHeight: unit(calculatedHeight),
                overflow: "hidden",
                whiteSpace: "nowrap",
                textOverflow: "ellipsis",
                top: 0,
                transform: "none",
                cursor: "text",
            },

            "& .suggestedTextInput-valueContainer": {
                $nest: {
                    [`.${classesInputBlock.inputText}`]: {
                        height: "auto",
                    },
                    "& > *": {
                        width: percent(100),
                    },
                },
            },
            "& .searchBar-submitButton": {
                position: "relative",
                minWidth: unit(scopeMinWidth + 2),
                flexBasis: unit(scopeMinWidth),
                minHeight: unit(vars.sizing.height),
                margin: unit(-1),
                $nest: {
                    "&:hover, &:focus": {
                        zIndex: 2,
                    },
                },
                borderTopLeftRadius: important(0),
                borderBottomLeftRadius: important(0),
                borderBottomRightRadius: importantUnit(borderRadius),
                borderTopRightRadius: importantUnit(borderRadius),
                paddingRight: importantUnit(buttonGlobalVariables().padding.horizontal + paddingOffset.right),
            },
            "& .wrap__control": {
                width: percent(100),
            },
            "& .searchBar__control": {
                border: 0,
                backgroundColor: colorOut(globalVars.elementaryColors.transparent),
                width: percent(100),
                flexBasis: percent(100),
                $nest: {
                    "&.searchBar__control--is-focused": {
                        boxShadow: "none",
                    },
                },
            },
            "& .searchBar__value-container": {
                position: "static",
                overflow: "auto",
                cursor: "text",
                display: "flex",
                justifyContent: "center",
                alignItems: "center",
                lineHeight: unit(globalVars.lineHeights.base * globalVars.fonts.size.medium),
                fontSize: unit(inputVars.font.size),
                height: unit(calculatedHeight),
                $nest: {
                    "& > div": {
                        width: percent(100),
                    },
                },
            },
            "& .searchBar__indicators": {
                display: "none",
            },
            "& .searchBar__input": {
                width: percent(100),
            },
            "& .searchBar__input input": {
                height: "auto",
                minHeight: 0,
                width: important(`100%`),
                borderRadius: important(0),
                lineHeight: unit(globalVars.lineHeights.base * globalVars.fonts.size.medium),
            },
            ...mediaQueries.oneColumnDown({
                $nest: {
                    "& .searchBar-submitButton": {
                        minWidth: 0,
                    },
                },
            }),
        },
    } as NestedCSSProperties);

    // The styles have been split here so they can be exported to the compatibility styles.
    const searchResultsStyles = {
        title: {
            ...fonts({
                size: globalVars.fonts.size.large,
                weight: globalVars.fonts.weights.semiBold,
                lineHeight: globalVars.lineHeights.condensed,
            }),
        } as NestedCSSProperties,
        meta: {
            ...fonts(globalVars.meta.text),
        } as NestedCSSProperties,
        excerpt: {
            marginTop: unit(searchResultsVariables().excerpt.margin),
            ...fonts({
                size: globalVars.fonts.size.medium,
                color: vars.results.fg,
                lineHeight: globalVars.lineHeights.excerpt,
            }),
        } as NestedCSSProperties,
    };

    const results = style("results", {
        position: "absolute",
        width: percent(100),
        backgroundColor: colorOut(vars.results.bg),
        color: colorOut(vars.results.fg),
        ...borderRadii({
            all: Math.min(parseInt(borderRadius.toString()), 6),
        }),
        $nest: {
            "&:empty": {
                display: important("none"),
            },
            "& .suggestedTextInput__placeholder": {
                color: colorOut(formElementVars.placeholder.color),
            },
            "& .suggestedTextInput-noOptions": {
                padding: px(12),
            },
            "& .suggestedTextInput-head": {
                ...flexHelper().middleLeft(),
                justifyContent: "space-between",
            },
            "& .suggestedTextInput-option": suggestedTextStyleHelper().option,
            "& .suggestedTextInput-menuItems": {
                margin: 0,
                padding: 0,
            },
            "& .suggestedTextInput-item": {
                listStyle: "none",
                $nest: {
                    "& + .suggestedTextInput-item": {
                        borderTop: `solid 1px ${globalVars.border.color.toString()}`,
                    },
                },
            },
            "& .suggestedTextInput-title": {
                ...searchResultsStyles.title,
            },

            "& .suggestedTextInput-title .suggestedTextInput-searchingFor": {
                fontWeight: globalVars.fonts.weights.normal,
            },
        },
    });

    const resultsAsModal = style("resultsAsModal", {
        position: "absolute",
        top: unit(vars.sizing.height),
        left: 0,
        overflow: "hidden",
        boxSizing: "border-box",
        ...shadow.dropDown(),
        zIndex: 2,
    });

    const clear = style("clear", {
        position: "relative",
        display: "flex",
        boxSizing: "border-box",
        height: unit(vars.sizing.height),
        width: unit(vars.sizing.height),
        color: colorOut(globalVars.mixBgAndFg(0.78)),
        transform: translateX(`${unit(4)}`),
        $nest: {
            "&, &.buttonIcon": {
                border: "none",
                boxShadow: "none",
            },
            "&:hover": {
                color: colorOut(vars.stateColors.hover),
            },
            "&:focus": {
                color: colorOut(vars.stateColors.focus),
            },
        },
    });

    const mainConditionalStyles = isInsetBordered
        ? {
              padding: `0 ${unit(borderVars.width)}`,
          }
        : {};

    const main = style("main", {
        flexGrow: 1,
        position: "relative",
        borderRadius: 0,
        ...mainConditionalStyles,
        $nest: {
            "&&.withoutButton.withoutScope": {
                ...borderRadii({
                    right: borderRadius,
                    left: borderRadius,
                }),
            },
            "&&.withButton.withScope": {
                ...borderRadii({
                    left: borderRadius,
                }),
            },
            "&&.withoutButton.withScope": {
                ...borderRadii({
                    right: borderRadius,
                }),
            },
            "&&.withButton.withoutScope": {
                ...borderRadii({
                    left: borderRadius,
                    right: 0,
                }),
            },
            "&.isFocused": {
                zIndex: isOuterBordered ? 3 : 1,
            },
            "&.isHovered": {
                zIndex: isOuterBordered ? 3 : undefined,
            },
        },
    });

    const valueContainerConditionalStyles = isInsetBordered
        ? {
              position: "absolute",
              top: 0,
              left: 0,
              height: calc(`100% - 2px`),
              minHeight: calc(`100% - 2px`),
              width: calc(`100% - 2px`),
              margin: unit(1),
          }
        : {
              height: percent(100),
          };

    const valueContainer = style("valueContainer", {
        $nest: {
            "&&&": {
                ...valueContainerConditionalStyles,
                display: "flex",
                alignItems: "center",
                backgroundColor: colorOut(vars.input.bg),
                color: colorOut(vars.input.fg),
                cursor: "text",
                flexWrap: "nowrap",
                justifyContent: "flex-start",
                borderRadius: 0,
                zIndex: isInsetBordered ? 2 : undefined,
                ...defaultTransition("border-color"),
                ...borders({
                    color: borderColor,
                }),
                ...borderRadii({
                    all: borderRadius,
                }),
            } as NestedCSSProperties,
            "&&&:not(.isFocused)": {
                borderColor: isInsetBordered ? colorOut(vars.input.bg) : vars.border.color,
            },
            "&&&:not(.isFocused).isHovered": {
                borderColor: colorOut(vars.stateColors.hover),
            },
            [`&&&&.isFocused .${main}`]: {
                borderColor: colorOut(vars.stateColors.hover),
            },

            // -- Text Input Radius --
            // Both sides round
            "&&.inputText.withoutButton.withoutScope": {
                paddingLeft: unit(vars.searchIcon.gap),
                ...borderRadii({
                    all: borderRadius,
                }),
            },
            // Right side flat
            "&&.inputText.withButton.withoutScope": {
                paddingLeft: unit(vars.searchIcon.gap),
                paddingRight: unit(vars.searchIcon.gap),
                ...borderRadii({
                    left: borderRadius,
                    right: 0,
                }),
            },
            [`&&.inputText.withButton.withoutScope .${clear}`]: {
                ...absolutePosition.topRight(),
                bottom: 0,
                ...margins({
                    vertical: "auto",
                }),
                transform: translateX(unit(vars.border.width * 2)),
            },
            // Both sides flat
            "&&.inputText.withButton.withScope": {
                ...borderRadii({
                    left: 0,
                }),
            },
            // Left side flat
            "&&.inputText.withoutButton.withScope:not(.compactScope)": {
                paddingRight: unit(vars.searchIcon.gap),
            },
            "&&.inputText.withoutButton.withScope": {
                ...borderRadii({
                    right: borderRadius,
                    left: 0,
                }),
            },
        },
    } as NestedCSSProperties);

    // Has a search button attached.
    const compoundValueContainer = style("compoundValueContainer", {
        $nest: {
            "&&": {
                borderTopRightRadius: important(0),
                borderBottomRightRadius: important(0),
            },
        },
    });

    const actionButton = style("actionButton", {
        marginLeft: -borderVars.width,
    });

    const label = style("label", {
        display: "flex",
        alignItems: "center",
        justifyContent: "flex-start",
    });

    const scopeSeparatorStyle = isOuterBordered
        ? {
              display: "none",
          }
        : {
              ...absolutePosition.topRight(),
              bottom: 0,
              margin: "auto 0",
              height: percent(90),
              width: unit(borderVars.width),
              borderRight: singleBorder({
                  color: borderVars.color,
              }),
          };

    const scopeSeparator = style("scopeSeparator", scopeSeparatorStyle);

    const content = style("content", {
        display: "flex",
        alignItems: "stretch",
        justifyContent: "flex-start",
        position: "relative",
        backgroundColor: colorOut(vars.input.bg),
        width: percent(100),
        height: percent(100),
        $nest: {
            "&&.withoutButton.withoutScope": {
                ...borderRadii({
                    all: borderRadius,
                }),
            },
            "&&.withButton.withScope": {},
            "&&.withoutButton.withScope": {
                ...borderRadii({
                    all: borderRadius,
                }),
            },
            "&&.withButton.withoutScope": {
                ...borderRadii({
                    left: borderRadius,
                    right: 0,
                }),
            },
            [`&.hasFocus .${scopeSeparator}`]: {
                display: "none",
            },
            [`&.hasFocus .${valueContainer}`]: {
                borderColor: colorOut(vars.stateColors.focus),
            },
        },
    });

    // special selector
    const heading = style("heading", {
        $nest: {
            "&&": {
                marginBottom: unit(vars.heading.margin),
            },
        },
    });

    const icon = style("icon", {});

    const iconContainer = (alignRight?: boolean) => {
        const { compact = false } = overwrites || {};
        const horizontalPosition = unit(compact ? vars.scope.compact.padding : vars.scope.padding);

        const conditionalStyle = alignRight
            ? {
                  right: horizontalPosition,
              }
            : {
                  left: horizontalPosition,
              };

        return style("iconContainer", {
            ...buttonResetMixin(),
            // ...pointerEvents(), // messes with hover of input. It'll click on the input anyways
            position: "absolute",
            top: 0,
            bottom: 0,
            ...conditionalStyle,
            height: percent(100),
            display: "flex",
            alignItems: "center",
            justifyContent: "center",
            zIndex: 5,
            outline: 0,
            color: colorOut(vars.searchIcon.fg),
            $nest: {
                [`.${icon}`]: {
                    width: unit(vars.searchIcon.width),
                    height: unit(vars.searchIcon.height),
                },
                [`&&& + .searchBar-valueContainer`]: {
                    paddingLeft: unit(vars.searchIcon.gap),
                },
                "&:hover": {
                    color: colorOut(vars.stateColors.hover),
                },
                "&:focus": {
                    color: colorOut(vars.stateColors.focus),
                },
            },
        });
    };

    const iconContainerBigInput = style("iconContainerBig", {
        $nest: {
            "&&": {
                height: unit(vars.sizing.height),
            },
        },
    });

    const menu = style("menu", {
        display: "flex",
        alignItems: "flex-start",
        justifyContent: "flex-start",
        $nest: {
            "&.hasFocus .searchBar-valueContainer": {
                borderColor: colorOut(vars.stateColors.focus),
            },
            "&&": {
                position: "relative",
            },
            "& .searchBar__menu-list": {
                maxHeight: calc(`100vh - ${unit(titleBarVars.sizing.height)}`),
                width: percent(100),
            },
            "& .searchBar__input": {
                color: colorOut(globalVars.mainColors.fg),
                width: percent(100),
                display: important("block"),
                $nest: {
                    input: {
                        width: important(percent(100).toString()),
                        lineHeight: globalVars.lineHeights.base,
                    },
                },
            },
            "& .suggestedTextInput-menu": {
                borderRadius: unit(globalVars.border.radius),
                marginTop: unit(-formElementVars.border.width),
                marginBottom: unit(-formElementVars.border.width),
            },
            "&:empty": {
                display: "none",
            },
        },
    });

    const scopeSelect = style("scopeSelect", {
        display: "flex",
        width: calc("100%"),
        height: calc("100%"),
        justifyContent: "center",
        alignItems: "stretch",

        $nest: {
            "& .selectBox-dropDown": {
                position: "relative",
                padding: isInsetBordered ? unit(vars.border.width) : undefined,
                width: percent(100),
                height: percent(100),
            },
        },
    });

    const scopeToggleConditionalStyles = isInsetBordered
        ? {
              position: "absolute",
              top: 0,
              left: 0,
              height: calc(`100% - 2px`),
              width: calc(`100% - 2px`),
              margin: unit(vars.noBorder.offset),
          }
        : {
              width: percent(100),
              height: percent(100),
          };

    const scopeToggle = style("scopeToggle", {
        display: "flex",
        justifyContent: "stretch",
        alignItems: "center",
        lineHeight: "2em",
        flexWrap: "nowrap",
        ...scopeToggleConditionalStyles,
        ...borders({
            color: borderColor,
        }),
        ...userSelect(),
        backgroundColor: colorOut(globalVars.mainColors.bg),
        ...paddings({
            horizontal: compact ? vars.scope.compact.padding : vars.scope.padding,
        }),
        outline: 0,
        ...borderRadii({
            left: borderRadius,
            right: 0,
        }),
        $nest: {
            [`& .${selectBoxClasses().buttonIcon}`]: {
                width: unit(vars.scopeIcon.width),
                flexBasis: unit(vars.scopeIcon.width),
                height: unit(vars.scopeIcon.width * vars.scopeIcon.ratio),
                margin: "0 0 0 auto",
                color: colorOut(vars.input.fg),
            },
            "&:focus, &:hover, &:active, &.focus-visible": {
                zIndex: 3,
            },
            "&:hover": {
                borderColor: colorOut(vars.stateColors.hover),
            },
            "&:active": {
                borderColor: colorOut(vars.stateColors.active),
            },
            "&:focus, &.focus-visible": {
                borderColor: colorOut(vars.stateColors.focus),
            },

            // Nested above doesn't work
            [`&:focus .${scopeSeparator},
                &:hover .${scopeSeparator},
                &:active .${scopeSeparator},
                &.focus-visible .${scopeSeparator}`]: {
                display: "none",
            },
        },
    } as NestedCSSProperties);

    const scopeLabelWrap = style("scopeLabelWrap", {
        display: "flex",
        textOverflow: "ellipsis",
        whiteSpace: "nowrap",
        overflow: "hidden",
        lineHeight: 2,
        color: colorOut(vars.input.fg),
    });

    const scope = style("scope", {
        position: "relative",
        minHeight: unit(vars.sizing.height),
        width: unit(compact ? vars.scope.compact.width : vars.scope.width),
        flexBasis: unit(compact ? vars.scope.compact.width : vars.scope.width),
        display: "flex",
        alignItems: "stretch",
        justifyContent: "flex-start",
        backgroundColor: colorOut(globalVars.mainColors.bg),
        paddingRight: isInsetBordered ? unit(vars.border.width) : undefined,
        transform: isOuterBordered ? translateX(unit(vars.border.width)) : undefined,
        zIndex: isOuterBordered ? 2 : undefined,
        ...borderRadii({
            left: borderRadius,
            right: 0,
        }),
        $nest: {
            [`
                &.isOpen .${scopeSeparator},
                &.isActive .${scopeSeparator},
            `]: {
                display: "none",
            },
            [`& .${scopeSelect}`]: {
                ...borderRadii({
                    left: borderRadius,
                    right: 0,
                }),
            },
            [`& .selectBox-dropDown`]: {
                ...borderRadii({
                    left: borderRadius,
                    right: 0,
                }),
            },
            [`& .${scopeToggle}`]: {
                ...borderRadii({
                    left: borderRadius,
                    right: 0,
                }),
            },
            [`&.isCompact .${scopeToggle}`]: {
                paddingLeft: unit(12),
                paddingRight: unit(12),
            },
            [`& + .${main}`]: {
                maxWidth: calc(`100% - ${unit(compact ? vars.scope.compact.width : vars.scope.width)}`),
                flexBasis: calc(`100% - ${unit(compact ? vars.scope.compact.width : vars.scope.width)}`),
            },
        },
    });

    const wrap = style("wrap", {
        display: "flex",
        height: percent(100),
        width: percent(100),
    });

    const form = style("form", {
        display: "flex",
        width: percent(100),
        flexWrap: "nowrap",
        alignItems: "center",
    });

    const closeButton = style("closeButton", {
        marginLeft: unit(6),
        borderRadius: "6px",
    });

    const compactIcon = style("compactIcon", {
        $nest: {
            "&&": {
                width: unit(vars.searchIcon.width),
                height: unit(vars.searchIcon.height),
            },
        },
    });

    const standardContainer = style("standardContainer", {
        display: "block",
        position: "relative",
        height: unit(vars.sizing.height),
    });

    return {
        root,
        independentRoot,
        compoundValueContainer,
        valueContainer,
        actionButton,
        label,
        clear,
        form,
        content,
        heading,
        iconContainer,
        iconContainerBigInput,
        icon,
        results,
        resultsAsModal,
        menu,
        searchResultsStyles, // for compatibility
        scope,
        scopeToggle,
        scopeSeparator,
        scopeSelect,
        scopeLabelWrap,
        closeButton,
        wrap,
        main,
        compactIcon,
        standardContainer,
    };
});
