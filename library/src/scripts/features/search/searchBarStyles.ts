/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { borderRadii, borders, colorOut, unit, paddings, importantUnit } from "@library/styles/styleHelpers";
import { calc, important, percent, px } from "csx";
import { titleBarVariables } from "@library/headers/titleBarStyles";
import { buttonClasses, buttonResetMixin, buttonVariables } from "@library/forms/buttonStyles";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { shadowHelper } from "@library/styles/shadowHelpers";
import { inputBlockClasses } from "@library/forms/InputBlockStyles";
import { inputVariables } from "@library/forms/inputStyles";
import { splashClasses } from "@library/splash/splashStyles";

export const searchBarVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const formElementVars = formElementsVariables();
    const themeVars = variableFactory("searchBar");

    const search = themeVars("search", {
        minWidth: 109,
    });

    const sizing = themeVars("sizing", {
        height: 40,
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
    const classesButton = buttonClasses();
    const formElementVars = formElementsVariables();
    const mediaQueries = layoutVariables().mediaQueries();
    const style = styleFactory("searchBar");
    const shadow = shadowHelper();
    const classesInputBlock = inputBlockClasses();

    const root = style(
        {
            cursor: "pointer",
            $nest: {
                "& .searchBar__placeholder": {
                    color: colorOut(formElementVars.placeholder.color),
                    margin: "auto",
                },

                "& .suggestedTextInput-valueContainer": {
                    $nest: {
                        [`.${classesInputBlock.inputText}`]: {
                            height: "auto",
                        },
                    },
                },
                "& .searchBar-submitButton": {
                    position: "relative",
                    marginLeft: unit(-globalVars.border.width * 2),
                    minWidth: unit(vars.search.minWidth),
                    flexBasis: unit(vars.search.minWidth),
                    minHeight: unit(vars.sizing.height),
                    $nest: {
                        "&:hover, &:focus": {
                            zIndex: 1,
                        },
                    },
                },
                "& .searchBar__control": {
                    display: "flex",
                    flex: 1,
                    border: 0,
                    backgroundColor: "transparent",
                    height: percent(100),
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
                    overflow: "auto",
                    cursor: "text",
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
                    width: important(`100%`),
                },
            },
        },
        mediaQueries.oneColumnDown({
            $nest: {
                "& .searchBar-submitButton": {
                    minWidth: 0,
                },
            },
        }),
    );

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
            "& .suggestedTextInput-option": {
                ...buttonResetMixin(),
                width: percent(100),
                ...paddings({
                    vertical: 9,
                    horizontal: 12,
                }),
                textAlign: "left",
                display: "block",
                color: "inherit",
                $nest: {
                    "&:hover, &:focus, &.isFocused": {
                        color: "inherit",
                        backgroundColor: globalVars.states.hover.color.toString(),
                    },
                },
            },
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
        },
    });

    const resultsAsModal = style("results", {
        position: "absolute",
        top: unit(vars.sizing.height),
        left: 0,
        overflow: "hidden",
        ...borders({
            radius: vars.results.borderRadius,
        }),
        ...shadow.dropDown(),
        zIndex: 1,
    });

    const valueContainer = style("valueContainer", {
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
        ...borderRadii({
            right: 0,
            left: vars.border.radius,
        }),
        $nest: {
            "&&&": {
                display: "flex",
                flexWrap: "nowrap",
                alignItems: "center",
                justifyContent: "flex-start",
                paddingLeft: unit(vars.searchIcon.gap),
            },
            "&.noSearchButton": {
                ...borderRadii({
                    right: importantUnit(vars.border.radius),
                }),
            },
        },
    });

    // Has a search button attached.
    const compoundValueContainer = style("compoundValueContainer", {
        $nest: {
            "&&": {
                borderTopRightRadius: 0,
                borderBottomRightRadius: 0,
            },
        },
    });

    const actionButton = style("actionButton", {
        marginLeft: -vars.border.width,
        ...borderRadii({
            left: important("0"),
            right: important(vars.border.radius),
        }),
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
        $nest: {
            [`&:not(.${splashClasses({}).content}).hasFocus .searchBar-valueContainer`]: {
                borderColor: colorOut(globalVars.mainColors.primary),
            },
        },
    });

    const form = style("form", {
        display: "block",
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
        minHeight: unit(vars.sizing.height),
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
    };
});
