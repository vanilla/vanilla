/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { borders, colorOut, unit } from "@library/styles/styleHelpers";
import { calc, important, percent, px } from "csx";

import { vanillaHeaderVariables } from "@library/headers/vanillaHeaderStyles";
import { buttonClasses } from "@library/forms/buttonStyles";
import { layoutVariables } from "@library/layout/layoutStyles";

export const searchBarVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const formElementVars = formElementsVariables();
    const themeVars = variableFactory("searchBar");

    const search = themeVars("search", {
        minWidth: 109,
    });

    const searchIcon = themeVars("searchIcon", {
        gap: 32,
        height: 13,
        width: 13,
    });

    const sizing = themeVars("sizing", {
        height: 40,
    });

    const placeholder = themeVars("placeholder", {
        color: formElementVars.placeholder.color,
    });

    const heading = themeVars("heading", {
        margin: 5,
    });

    const input = themeVars("input", {
        fg: globalVars.mainColors.fg,
        bg: globalVars.mainColors.bg,
        border: {
            color: globalVars.mainColors.fg,
        },
    });

    const results = themeVars("results", {
        fg: globalVars.mainColors.fg,
        bg: globalVars.mainColors.bg,
    });

    return {
        search,
        searchIcon,
        sizing,
        placeholder,
        input,
        heading,
        results,
    };
});

export const searchBarClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = searchBarVariables();
    const vanillaHeaderVars = vanillaHeaderVariables();
    const classesButton = buttonClasses();
    const formElementVars = formElementsVariables();
    const mediaQueries = layoutVariables().mediaQueries();
    const style = styleFactory("searchBar");

    const root = style(
        {
            cursor: "pointer",
            $nest: {
                "& .suggestedTextInput-clear": {
                    color: colorOut(globalVars.mainColors.fg),
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
                },

                "& .searchBar__placeholder": {
                    color: colorOut(globalVars.mixBgAndFg(0.5)),
                    margin: "auto",
                },

                "& .suggestedTextInput-valueContainer": {
                    $nest: {
                        ".inputBlock-inputText": {
                            height: "auto",
                        },
                    },
                },
                "& .searchBar-submitButton": {
                    position: "relative",
                    borderTopLeftRadius: important(0),
                    borderBottomLeftRadius: important(0),
                    marginLeft: unit(-1),
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
                                    ...borders(classesButton.standard.border),
                                },
                            },
                        },
                    },
                },
                "& .searchBar__value-container": {
                    overflow: "auto",
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
                "& .searchBar__menu-list": {
                    maxHeight: calc(`100vh - ${unit(vanillaHeaderVars.sizing.height)}`),
                },
            },
        },
        mediaQueries.oneColumn({
            $nest: {
                "& .searchBar-submitButton": {
                    minWidth: 0,
                },
            },
        }),
    );

    const results = style("results", {
        backgroundColor: colorOut(vars.results.bg),
        color: colorOut(vars.results.fg),
        $nest: {
            ".suggestedTextInput__placeholder": {
                color: colorOut(formElementVars.placeholder.color),
            },
            ".suggestedTextInput-noOptions": {
                padding: px(12),
            },
            ".suggestedTextInput-option": {
                width: percent(100),
                padding: px(12),
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
            ".suggestedTextInput-menu": {
                borderRadius: unit(globalVars.border.radius),
                marginTop: unit(-formElementVars.border.width),
                marginBottom: unit(-formElementVars.border.width),
            },
            ".suggestedTextInput-item": {
                $nest: {
                    "& + .suggestedTextInput-item": {
                        borderTop: `solid 1px ${globalVars.border.color.toString()}`,
                    },
                },
            },
        },
    });

    const valueContainer = style("valueContainer", {
        display: "flex",
        alignItems: "center",
        borderRight: 0,
        paddingTop: 0,
        paddingBottom: 0,
        backgroundColor: colorOut(vars.input.bg),
        color: colorOut(vars.input.fg),
        $nest: {
            "&&&": {
                display: "flex",
                flexWrap: "nowrap",
                alignItems: "center",
                justifyContent: "flex-start",
                paddingLeft: unit(vars.searchIcon.gap),
            },
        },
    });

    // Has a search button attached.
    const compoundValueContainer = style("compoundValueContainer", {
        borderTopRightRadius: 0,
        borderBottomRightRadius: 0,
    });

    const actionButton = style("actionButton", {
        marginLeft: "auto",
        marginRight: -(globalVars.buttonIcon.offset + 3), // the "3" is to offset the pencil
        opacity: 0.8,
        $nest: {
            "&:hover": {
                opacity: 1,
            },
        },
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
        color: vanillaHeaderVars.colors.fg.toString(),
    });

    const form = style("form", {
        display: "block",
    });

    const content = style("content", {
        display: "flex",
        alignItems: "flex-start",
        justifyContent: "flex-start",
        position: "relative",
        minHeight: unit(vars.sizing.height),
        $nest: {
            "&.hasFocus .searchBar-valueContainer": {
                borderColor: colorOut(globalVars.mainColors.primary),
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

    const iconContainer = style("iconContainer", {
        position: "absolute",
        top: 0,
        bottom: 0,
        left: "2px",
        height: percent(100),
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        width: unit(vars.searchIcon.gap),
        color: colorOut(vars.input.fg),
    });
    const icon = style("icon", {
        width: unit(vars.searchIcon.width),
        height: unit(vars.searchIcon.height),
        color: colorOut(vars.input.fg),
    });

    return {
        root,
        valueContainer,
        compoundValueContainer,
        actionButton,
        label,
        clear,
        form,
        content,
        heading,
        iconContainer,
        icon,
        results,
    };
});
