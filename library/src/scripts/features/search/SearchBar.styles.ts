/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { useThemeCache } from "@library/styles/themeCache";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import {
    absolutePosition,
    borderRadii,
    defaultTransition,
    flexHelper,
    getVerticalPaddingForTextInput,
    importantUnit,
    singleBorder,
    userSelect,
} from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { Mixins } from "@library/styles/Mixins";
import { calc, important, percent, px, translateX } from "csx";
import { titleBarVariables } from "@library/headers/TitleBar.variables";
import { buttonGlobalVariables } from "@library/forms/Button.variables";
import { buttonResetMixin } from "@library/forms/buttonMixins";
import { oneColumnVariables } from "@library/layout/Section.variables";
import { shadowHelper } from "@library/styles/shadowHelpers";
import { inputBlockClasses } from "@library/forms/InputBlockStyles";
import { css, CSSObject } from "@emotion/css";
import { inputVariables } from "@library/forms/inputStyles";
import { suggestedTextStyleHelper } from "@library/features/search/suggestedTextStyles";
import { searchResultsVariables } from "@library/features/search/searchResultsStyles";
import { paddingOffsetBasedOnBorderRadius } from "@library/forms/paddingOffsetFromBorderRadius";
import { selectBoxClasses } from "@library/forms/select/selectBoxStyles";
import { SearchBarPresets } from "@library/banner/SearchBarPresets";
import { IBorderRadiusValue } from "@library/styles/cssUtilsTypes";
import { searchBarVariables } from "./SearchBar.variables";
import { metasVariables } from "@library/metas/Metas.variables";

export interface ISearchBarOverwrites {
    borderRadius?: IBorderRadiusValue;
    compact?: boolean;
    preset?: SearchBarPresets;
}

export const searchBarClasses = useThemeCache((overwrites?: ISearchBarOverwrites) => {
    const shadow = shadowHelper();
    const classesInputBlock = inputBlockClasses();
    const vars = searchBarVariables();
    const { compact = vars.options.compact, borderRadius = vars.border.radius, preset = vars.options.preset } =
        overwrites || {};
    const globalVars = globalVariables();
    const metasVars = metasVariables();
    const layoutVars = oneColumnVariables();
    const inputVars = inputVariables();
    const titleBarVars = titleBarVariables();
    const formElementVars = formElementsVariables();
    const mediaQueries = layoutVars.mediaQueries();
    const borderStyle = overwrites && overwrites.preset ? overwrites.preset : vars.options.preset;

    const isOuterBordered = borderStyle === SearchBarPresets.BORDER;
    const isInsetBordered = borderStyle === SearchBarPresets.NO_BORDER;

    const borderColor = isInsetBordered ? vars.input.bg : vars.border.color;

    const independentRoot = css({
        display: "block",
        height: percent(100),
    });

    const verticalPadding = getVerticalPaddingForTextInput(
        vars.sizing.height,
        inputVars.font.size as number,
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

    const root = css({
        cursor: "pointer",
        ...{
            ".searchBar__placeholder": {
                color: ColorsUtils.colorOut(vars.placeholder.color),
                margin: "auto",
                height: styleUnit(calculatedHeight),
                lineHeight: styleUnit(calculatedHeight),
                overflow: "hidden",
                whiteSpace: "nowrap",
                textOverflow: "ellipsis",
                top: 0,
                transform: "none",
                cursor: "text",
                // Needed otherwise you can't use copy/paste in the context menu.
                // @see https://github.com/JedWatson/react-select/issues/612
                // @see https://github.com/vanilla/support/issues/3252
                pointerEvents: "none",
            },

            ".suggestedTextInput-valueContainer": {
                ...{
                    [`.${classesInputBlock.inputText}`]: {
                        height: "auto",
                    },
                    "& > *": {
                        width: percent(100),
                    },
                },
            },
            ".wrap__control": {
                width: percent(100),
            },
            ".searchBar__control": {
                border: 0,
                backgroundColor: ColorsUtils.colorOut(globalVars.elementaryColors.transparent),
                width: percent(100),
                flexBasis: percent(100),
                ...{
                    "&.searchBar__control--is-focused": {
                        boxShadow: "none",
                    },
                },
            },
            ".searchBar__value-container": {
                position: "static",
                overflow: "auto",
                cursor: "text",
                display: "flex",
                justifyContent: "center",
                alignItems: "center",
                lineHeight: styleUnit(globalVars.lineHeights.base * globalVars.fonts.size.medium),
                fontSize: styleUnit(inputVars.font.size),
                height: styleUnit(calculatedHeight),
                "& > div": {
                    width: percent(100),
                },
            },
            ".searchBar__indicators": {
                display: "none",
            },
            ".searchBar__input": {
                width: percent(100),
            },
            ".searchBar__input input": {
                margin: 0,
                height: "auto",
                minHeight: 0,
                width: important(`100%`),
                borderRadius: important(0),
                lineHeight: styleUnit(globalVars.lineHeights.base * globalVars.fonts.size.medium),
            },
        },
    });

    const submitButton = css({
        ...Mixins.button(compact ? vars.compactSubmitButton : vars.submitButton),
        margin: "-1px",
        "&:hover, &:focus": {
            zIndex: 2,
        },
        paddingRight: importantUnit(buttonGlobalVariables().padding.horizontal + paddingOffset.right),
        ...mediaQueries.oneColumnDown({
            minWidth: 0,
        }),
    });

    // The styles have been split here so they can be exported to the compatibility styles.
    const searchResultsStyles = {
        title: {
            ...Mixins.font({
                ...globalVars.fontSizeAndWeightVars("large", "semiBold"),
                lineHeight: globalVars.lineHeights.condensed,
            }),
        },
        meta: {
            ...Mixins.font(metasVars.font),
        },
        excerpt: {
            marginTop: styleUnit(searchResultsVariables().excerpt.margin),
            ...Mixins.font({
                ...globalVars.fontSizeAndWeightVars("medium"),
                color: vars.results.fg,
                lineHeight: globalVars.lineHeights.excerpt,
            }),
        },
    };

    const results = css({
        position: "absolute",
        width: percent(100),
        backgroundColor: ColorsUtils.colorOut(vars.results.bg),
        color: ColorsUtils.colorOut(vars.results.fg),
        ...borderRadii({
            all: Math.min(parseInt(borderRadius.toString()), 6),
        }),
        ...{
            "&:empty": {
                display: important("none"),
            },
            ".suggestedTextInput__placeholder": {
                color: ColorsUtils.colorOut(formElementVars.placeholder.color),
            },
            ".suggestedTextInput-noOptions": {
                padding: px(12),
            },
            ".suggestedTextInput-head": {
                ...flexHelper().middleLeft(),
                justifyContent: "space-between",
            },
            ".suggestedTextInput-groupHeading": {
                ...suggestedTextStyleHelper().groupHeading,
                margin: 0,
            },
            ".suggestedTextInput-option": {
                ...suggestedTextStyleHelper().option,
                margin: 0,
            },
            ".suggestedTextInput-menuItems": {
                margin: 0,
                padding: 0,
            },
            ".suggestedTextInput-item": {
                listStyle: "none",
                ...{
                    "& + .suggestedTextInput-item": {
                        borderTop: `solid 1px ${globalVars.border.color.toString()}`,
                    },
                },
            },
            ".suggestedTextInput-title": {
                ...searchResultsStyles.title,
            },
            ".suggestedTextInput-title .suggestedTextInput-searchingFor": {
                fontWeight: globalVars.fonts.weights.normal,
            },
        },
    });

    const resultsAsModal = css({
        position: "absolute",
        top: styleUnit(vars.sizing.height),
        left: 0,
        overflow: "hidden",
        boxSizing: "border-box",
        ...shadow.dropDown(),
        zIndex: 2,
    });

    const clear = css({
        position: "relative",
        display: "flex",
        boxSizing: "border-box",
        height: styleUnit(vars.sizing.height),
        width: styleUnit(vars.sizing.height),
        color: ColorsUtils.colorOut(globalVars.mixBgAndFg(0.78)),
        transform: translateX(`${styleUnit(4)}`),
        ...{
            "&, &.buttonIcon": {
                border: "none",
                boxShadow: "none",
            },
            "&:hover": {
                color: ColorsUtils.colorOut(vars.stateColors.hover),
            },
            "&:focus": {
                color: ColorsUtils.colorOut(vars.stateColors.focus),
            },
        },
    });

    const clearWithButtonWithoutScope = css({
        ...absolutePosition.topRight(),
        bottom: 0,
        ...Mixins.margin({
            vertical: "auto",
        }),
        transform: translateX(styleUnit(vars.border.width * 2)),
    });

    const mainConditionalStyles = isInsetBordered
        ? {
              padding: `0 ${styleUnit(borderVars.width)}`,
          }
        : {};

    const main = css({
        flexGrow: 1,
        position: "relative",
        borderRadius: 0,
        ...mainConditionalStyles,
        ...{
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
              margin: styleUnit(1),
          }
        : {
              height: percent(100),
          };

    const valueContainer = css({
        ...{
            "&&&": {
                ...valueContainerConditionalStyles,
                display: "flex",
                alignItems: "center",
                backgroundColor: ColorsUtils.colorOut(vars.input.bg),
                color: ColorsUtils.colorOut(vars.input.fg),
                cursor: "text",
                flexWrap: "nowrap",
                justifyContent: "flex-start",
                borderRadius: 0,
                zIndex: isInsetBordered ? 2 : undefined,
                ...defaultTransition("border-color"),
                ...Mixins.border({
                    color: borderColor,
                }),
                ...borderRadii({
                    all: borderRadius,
                }),
            },
            "&&&:not(.isFocused)": {
                borderColor: ColorsUtils.colorOut(isInsetBordered ? vars.input.bg : vars.border.color),
            },
            "&&&:not(.isFocused).isHovered": {
                borderColor: ColorsUtils.colorOut(vars.stateColors.hover),
            },
            [`&&&&.isFocused .${main}`]: {
                borderColor: ColorsUtils.colorOut(vars.stateColors.hover),
            },

            // -- Text Input Radius --
            // Both sides round
            "&&.inputText.withoutButton.withoutScope": {
                paddingLeft: styleUnit(vars.searchIcon.gap),
                ...borderRadii({
                    all: borderRadius,
                }),
            },
            // Right side flat
            "&&.inputText.withButton.withoutScope": {
                paddingLeft: styleUnit(vars.searchIcon.gap),
                paddingRight: styleUnit(vars.searchIcon.gap),
                ...borderRadii({
                    left: borderRadius,
                    right: 0,
                }),
            },
            // Both sides flat
            "&&.inputText.withButton.withScope": {
                ...borderRadii({
                    left: 0,
                }),
            },
            // Left side flat
            "&&.inputText.withoutButton.withScope:not(.compactScope)": {
                paddingRight: styleUnit(vars.searchIcon.gap),
            },
            "&&.inputText.withoutButton.withScope": {
                ...borderRadii({
                    right: borderRadius,
                    left: 0,
                }),
            },
        },
    } as CSSObject);

    // Has a search button attached.
    const compoundValueContainer = css({
        ...{
            "&&": {
                borderTopRightRadius: important(0),
                borderBottomRightRadius: important(0),
            },
        },
    });

    const actionButton = css({
        marginLeft: -borderVars.width,
    });

    const label = css({
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
              width: styleUnit(borderVars.width),
              borderRight: singleBorder({
                  color: borderVars.color,
              }),
          };

    const scopeSeparator = css(scopeSeparatorStyle);

    const content = css({
        display: "flex",
        alignItems: "stretch",
        justifyContent: "flex-start",
        position: "relative",
        backgroundColor: ColorsUtils.colorOut(vars.input.bg),
        width: percent(100),
        height: percent(100),
        ...{
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
                borderColor: ColorsUtils.colorOut(vars.stateColors.focus),
            },
        },
    });

    // special selector
    const heading = css({
        ...{
            "&&": {
                marginBottom: styleUnit(vars.heading.margin),
            },
        },
    });

    const icon = css({});

    const iconContainer = (alignRight?: boolean) => {
        const { compact = false } = overwrites || {};
        const horizontalPosition = styleUnit(compact ? vars.scope.compact.padding : vars.scope.padding);

        const conditionalStyle = alignRight
            ? {
                  right: horizontalPosition,
              }
            : {
                  left: horizontalPosition,
              };

        return css({
            ...buttonResetMixin(),
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
            color: ColorsUtils.colorOut(vars.searchIcon.fg),
            ...{
                [`.${icon}`]: {
                    width: styleUnit(vars.searchIcon.width),
                    height: styleUnit(vars.searchIcon.height),
                },
                [`&&& + .searchBar-valueContainer`]: {
                    paddingLeft: styleUnit(vars.searchIcon.gap),
                },
                "&:hover": {
                    color: ColorsUtils.colorOut(vars.stateColors.hover),
                },
                "&:focus": {
                    color: ColorsUtils.colorOut(vars.stateColors.focus),
                },
            },
        });
    };

    const iconContainerBigInput = css({
        ...{
            "&&": {
                height: styleUnit(vars.sizing.height),
            },
        },
    });

    const menu = css({
        display: "flex",
        alignItems: "flex-start",
        justifyContent: "flex-start",
        ...{
            "&.hasFocus .searchBar-valueContainer": {
                borderColor: ColorsUtils.colorOut(vars.stateColors.focus),
            },
            "&&": {
                position: "relative",
            },
            ".searchBar__menu-list": {
                maxHeight: calc(`100vh - ${styleUnit(titleBarVars.sizing.height)}`),
                width: percent(100),
            },
            ".searchBar__input": {
                color: ColorsUtils.colorOut(globalVars.mainColors.fg),
                width: percent(100),
                display: important("block"),
                ...{
                    input: {
                        width: important(percent(100).toString()),
                        lineHeight: globalVars.lineHeights.base,
                    },
                },
            },
            ".suggestedTextInput-menu": {
                borderRadius: styleUnit(globalVars.border.radius),
                marginTop: styleUnit(-formElementVars.border.width),
                marginBottom: styleUnit(-formElementVars.border.width),
            },
            "&:empty": {
                display: "none",
            },
        },
    });

    const { selectBoxDropdown, buttonIcon } = selectBoxClasses();

    const scopeSelect = css({
        display: "flex",
        width: calc("100%"),
        height: calc("100%"),
        justifyContent: "center",
        alignItems: "stretch",
        ...{
            [`.${selectBoxDropdown}`]: {
                position: "relative",
                padding: isInsetBordered ? styleUnit(vars.border.width) : undefined,
                width: percent(100),
                height: percent(100),
            },
        },
    });

    const scopeToggleConditionalStyles: CSSObject = isInsetBordered
        ? {
              position: "absolute",
              top: 0,
              left: 0,
              height: calc(`100% - 2px`),
              width: calc(`100% - 2px`),
              margin: styleUnit(vars.noBorder.offset),
          }
        : {
              width: percent(100),
              height: percent(100),
          };

    const scopeToggle = css({
        display: "flex",
        justifyContent: "stretch",
        alignItems: "center",
        lineHeight: "2em",
        flexWrap: "nowrap",
        ...scopeToggleConditionalStyles,
        ...Mixins.border({
            color: borderColor,
        }),
        ...userSelect(),
        backgroundColor: ColorsUtils.colorOut(globalVars.mainColors.bg),
        ...Mixins.padding({
            horizontal: compact ? vars.scope.compact.padding : vars.scope.padding,
        }),
        outline: 0,
        ...borderRadii({
            left: borderRadius,
            right: 0,
        }),
        ...{
            [`.${buttonIcon}`]: {
                width: styleUnit(vars.scopeIcon.width),
                flexBasis: styleUnit(vars.scopeIcon.width),
                height: styleUnit(vars.scopeIcon.width * vars.scopeIcon.ratio),
                margin: "0 0 0 auto",
                color: ColorsUtils.colorOut(vars.input.fg),
            },
            "&:focus, &:hover, &:active, &.focus-visible": {
                zIndex: 3,
            },
            "&:hover": {
                borderColor: ColorsUtils.colorOut(vars.stateColors.hover),
            },
            "&:active": {
                borderColor: ColorsUtils.colorOut(vars.stateColors.active),
            },
            "&:focus, &.focus-visible": {
                borderColor: ColorsUtils.colorOut(vars.stateColors.focus),
            },

            // Nested above doesn't work
            [`&:focus .${scopeSeparator},
                &:hover .${scopeSeparator},
                &:active .${scopeSeparator},
                &.focus-visible .${scopeSeparator}`]: {
                display: "none",
            },
        },
    });

    const scopeLabelWrap = css({
        display: "flex",
        textOverflow: "ellipsis",
        whiteSpace: "nowrap",
        overflow: "hidden",
        lineHeight: 2,
        color: ColorsUtils.colorOut(vars.input.fg),
    });

    const scope = css({
        position: "relative",
        minHeight: styleUnit(vars.sizing.height),
        width: styleUnit(compact ? vars.scope.compact.width : vars.scope.width),
        flexBasis: styleUnit(compact ? vars.scope.compact.width : vars.scope.width),
        display: "flex",
        alignItems: "stretch",
        justifyContent: "flex-start",
        backgroundColor: ColorsUtils.colorOut(globalVars.mainColors.bg),
        paddingRight: isInsetBordered ? styleUnit(vars.border.width) : undefined,
        transform: isOuterBordered ? translateX(styleUnit(vars.border.width)) : undefined,
        zIndex: isOuterBordered ? 2 : undefined,
        ...borderRadii({
            left: borderRadius,
            right: 0,
        }),
        ...{
            [`
                &.isOpen .${scopeSeparator},
                &.isActive .${scopeSeparator}
            `]: {
                display: "none",
            },
            [`.${scopeToggle}`]: {
                ...borderRadii({
                    left: borderRadius,
                    right: 0,
                }),
            },
            [`&.isCompact .${scopeToggle}`]: {
                paddingLeft: styleUnit(12),
                paddingRight: styleUnit(12),
            },
            [`& + .${main}`]: {
                maxWidth: calc(`100% - ${styleUnit(compact ? vars.scope.compact.width : vars.scope.width)}`),
                flexBasis: calc(`100% - ${styleUnit(compact ? vars.scope.compact.width : vars.scope.width)}`),
            },
        },
    });

    const wrap = css({
        display: "flex",
        height: percent(100),
        width: percent(100),
    });

    const form = css({
        display: "flex",
        width: percent(100),
        flexWrap: "nowrap",
        alignItems: "center",
    });

    const closeButton = css({
        marginLeft: styleUnit(6),
        borderRadius: "6px",
    });

    const compactIcon = css({
        ...{
            "&&": {
                width: styleUnit(vars.searchIcon.width),
                height: styleUnit(vars.searchIcon.height),
            },
        },
    });

    const standardContainer = css({
        display: "block",
        position: "relative",
        height: styleUnit(vars.sizing.height),
        marginBottom: styleUnit(globalVars.gutter.size),
    });

    const firstItemBorderTop = css({
        borderTop: `solid 1px ${globalVars.border.color.toString()}`,
    });

    return {
        root,
        submitButton,
        independentRoot,
        compoundValueContainer,
        valueContainer,
        actionButton,
        label,
        clear,
        clearWithButtonWithoutScope,
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
        firstItemBorderTop,
    };
});
