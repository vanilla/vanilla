/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { formElementsVariables } from "@library/forms/formElementStyles";
import {
    borderRadii,
    borders,
    colorOut,
    unit,
    getVerticalPaddingForTextInput,
    fonts,
    flexHelper,
    importantUnit,
} from "@library/styles/styleHelpers";
import { calc, important, percent, px, translateX } from "csx";
import { titleBarVariables } from "@library/headers/titleBarStyles";
import { buttonGlobalVariables, buttonResetMixin, buttonVariables } from "@library/forms/buttonStyles";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { shadowHelper } from "@library/styles/shadowHelpers";
import { inputBlockClasses } from "@library/forms/InputBlockStyles";
import { NestedCSSProperties } from "typestyle/lib/types";
import { inputVariables } from "@library/forms/inputStyles";
import { suggestedTextStyleHelper } from "@library/features/search/suggestedTextStyles";
import { searchResultsVariables } from "@library/features/search/searchResultsStyles";
import { paddingOffsetBasedOnBorderRadius } from "@library/forms/paddingOffsetFromBorderRadius";

export const searchBarVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const formElementVars = formElementsVariables();
    const themeVars = variableFactory("searchBar");

    const search = themeVars("search", {
        minWidth: 109,
        fullBorderRadius: {
            extraHorizontalPadding: 10, // Padding when you have fully rounded border radius. Will be applied based on the amount of border radius. Set to "undefined" to turn off
        },
    });

    const sizing = themeVars("sizing", {
        height: formElementVars.sizing.height,
    });

    const placeholder = themeVars("placeholder", {
        color: formElementVars.placeholder.color,
    });

    const heading = themeVars("heading", {
        margin: 12,
    });

    const border = themeVars("border", {
        width: globalVars.border.width,
        radius: globalVars.border.radius,
    });

    const input = themeVars("input", {
        fg: globalVars.mainColors.fg,
        bg: globalVars.mainColors.bg,
    });

    const searchIcon = themeVars("searchIcon", {
        gap: 32,
        height: 13,
        width: 14,
        fg: input.fg.fade(0.7),
        padding: {
            right: 5,
        },
    });

    const results = themeVars("results", {
        fg: globalVars.mainColors.fg,
        bg: globalVars.mainColors.bg,
        borderRadius: globalVars.border.radius,
    });

    return {
        search,
        searchIcon,
        sizing,
        placeholder,
        input,
        heading,
        results,
        border,
    };
});

export const searchBarClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = searchBarVariables();
    const titleBarVars = titleBarVariables();
    const formElementVars = formElementsVariables();
    const mediaQueries = layoutVariables().mediaQueries();
    const style = styleFactory("searchBar");
    const shadow = shadowHelper();
    const classesInputBlock = inputBlockClasses();
    const inputVars = inputVariables();

    const independentRoot = style("independentRoot", {
        position: "relative",
    });

    const verticalPadding = getVerticalPaddingForTextInput(
        vars.sizing.height,
        inputVars.font.size,
        formElementVars.border.width * 2,
    );
    const calculatedHeight = vars.sizing.height - verticalPadding * 2 - formElementVars.border.width * 2;

    const buttonBorderRadius = buttonVariables().primary.borders.radius;

    const paddingOffset = paddingOffsetBasedOnBorderRadius({
        radius: buttonBorderRadius,
        extraPadding: vars.search.fullBorderRadius.extraHorizontalPadding,
        height: vars.sizing.height,
        side: "right",
    });

    const root = style({
        cursor: "pointer",
        $nest: {
            "& .searchBar__placeholder": {
                color: colorOut(formElementVars.placeholder.color),
                margin: "auto",
                height: unit(calculatedHeight),
                lineHeight: unit(calculatedHeight),
                top: 0,
                transform: "none",
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
                minWidth: unit(vars.search.minWidth),
                flexBasis: unit(vars.search.minWidth),
                minHeight: unit(vars.sizing.height),
                $nest: {
                    "&:hover, &:focus": {
                        zIndex: 1,
                    },
                },
                borderTopLeftRadius: important(0),
                borderBottomLeftRadius: important(0),
                paddingRight: importantUnit(buttonGlobalVariables().padding.horizontal + paddingOffset.right),
            },
            "& .searchBar__control": {
                border: 0,
                backgroundColor: colorOut(globalVars.elementaryColors.transparent),
                width: percent(100),
                maxWidth: calc(`100% - ${unit(vars.sizing.height)}`),
                $nest: {
                    "&.searchBar__control--is-focused": {
                        boxShadow: "none",
                        $nest: {
                            "&.inputText": {
                                borderTopRightRadius: 0,
                                borderBottomRightRadius: 0,
                                ...borders(buttonVariables().standard.borders),
                            },
                        },
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
        ...borders({
            radius: vars.results.borderRadius,
        }),
        boxSizing: "border-box",
        ...shadow.dropDown(),
        zIndex: 1,
    });

    const valueContainer = (mirrorLeftRadius = false) => {
        return style("valueContainer", {
            display: "flex",
            alignItems: "center",
            width: percent(100),
            paddingTop: 0,
            paddingBottom: 0,
            paddingRight: 0,
            height: unit(vars.sizing.height),
            backgroundColor: colorOut(vars.input.bg),
            color: colorOut(vars.input.fg),
            cursor: "text",
            transition: `border ${globalVars.animation.defaultTiming} ${globalVars.animation.defaultTiming}`,
            $nest: {
                "&&&": {
                    display: "flex",
                    flexWrap: "nowrap",
                    alignItems: "center",
                    justifyContent: "flex-start",
                    paddingLeft: unit(vars.searchIcon.gap),
                    paddingRight: unit(vars.searchIcon.padding.right),
                    ...borderRadii({
                        left: vars.border.radius,
                        right: mirrorLeftRadius ? important(vars.border.radius) : important(0),
                    }),
                },
            },
        });
    };

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
        marginLeft: -vars.border.width,
    });

    const label = style("label", {
        display: "flex",
        alignItems: "center",
        justifyContent: "flex-start",
    });

    const clear = style("clear", {
        position: "relative",
        display: "flex",
        boxSizing: "border-box",
        height: unit(vars.sizing.height),
        width: unit(vars.sizing.height),
        color: colorOut(globalVars.mixBgAndFg(0.78)),
        transform: translateX(`${unit(8)}`),
        $nest: {
            "&, &.buttonIcon": {
                border: "none",
                boxShadow: "none",
            },
            "&:hover": {
                color: colorOut(globalVars.mainColors.primary),
            },
            "&:focus": {
                color: colorOut(globalVars.mainColors.primary),
            },
        },
    });

    const content = style("content", {
        display: "flex",
        alignItems: "flex-start",
        justifyContent: "flex-start",
        position: "relative",
        height: unit(vars.sizing.height),
        width: percent(100),
    });

    const form = style("form", {
        display: "block",
        height: percent(100),
    });

    // special selector
    const heading = style("heading", {
        $nest: {
            "&&": {
                marginBottom: unit(vars.heading.margin),
            },
        },
    });

    const icon = style("icon", {
        color: colorOut(vars.searchIcon.fg),
    });

    const iconContainer = style("iconContainer", {
        ...buttonResetMixin(),
        position: "absolute",
        top: 0,
        bottom: 0,
        left: unit(globalVars.border.width * 2),
        height: unit(formElementVars.sizing.height),
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        width: unit(vars.searchIcon.gap),
        zIndex: 1,
        cursor: "text",
        outline: 0,
        $nest: {
            [`.${icon}`]: {
                width: unit(vars.searchIcon.width),
                height: unit(vars.searchIcon.height),
            },
        },
    });

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
                borderColor: colorOut(globalVars.mainColors.primary),
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
    };
});
