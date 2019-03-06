/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { formElementsVariables } from "@library/components/forms/formElementStyles";
import { buttonVariables } from "@library/styles/buttonStyles";
import { globalVariables } from "@library/styles/globalStyleVars";
import { layoutVariables } from "@library/styles/layoutStyles";
import { componentThemeVariables, debugHelper, toStringColor, unit } from "@library/styles/styleHelpers";
import { useThemeCache } from "@library/styles/styleUtils";
import { vanillaHeaderVariables } from "@library/styles/vanillaHeaderStyles";
import { calc, important, percent, px } from "csx";
import { style } from "typestyle";

export const searchBarVariables = useThemeCache(() => {
    const formElementVars = formElementsVariables();
    const themeVars = componentThemeVariables("searchBar");

    const search = {
        minWidth: 109,
        ...themeVars.subComponentStyles("search"),
    };

    const searchIcon = {
        gap: 32,
        height: 13,
        width: 13,
        ...themeVars.subComponentStyles("searchIcon"),
    };

    const sizing = {
        height: formElementVars.sizing.height,
        ...themeVars.subComponentStyles("sizing"),
    };

    const placeholder = {
        color: formElementVars.colors.placeholder,
        ...themeVars.subComponentStyles("placeholder"),
    };

    const heading = {
        margin: 5,
    };

    return { search, searchIcon, sizing, placeholder, heading };
});

export const searchBarClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = searchBarVariables();
    const vanillaHeaderVars = vanillaHeaderVariables();
    const debug = debugHelper("searchBar");
    const buttonVars = buttonVariables();
    const formElementVars = formElementsVariables();
    const mediaQueries = layoutVariables().mediaQueries();

    const root = style(
        {
            ...debug.name(),
            cursor: "pointer",
            $nest: {
                "& .suggestedTextInput-clear": {
                    $nest: {
                        "&, &.buttonIcon": {
                            border: "none",
                            boxShadow: "none",
                            color: globalVars.mainColors.primary.toString(),
                        },
                    },
                },

                "& .searchBar__placeholder": {
                    color: vars.placeholder.color.toString(),
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
                    borderTopLeftRadius: 0,
                    borderBottomLeftRadius: 0,
                    marginLeft: -1,
                    minWidth: unit(vars.search.minWidth),
                    flexBasis: unit(vars.search.minWidth),
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
                                    borderColor: buttonVars.standard.focus.borderColor.toString(),
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

    const results = style({
        $nest: {
            ".suggestedTextInput__placeholder": {
                color: toStringColor(formElementVars.colors.placeholder),
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
        ...debug.name("results"),
    });

    const valueContainer = style({
        display: "flex",
        alignItems: "center",
        borderRight: 0,
        paddingTop: 0,
        paddingBottom: 0,
        $nest: {
            "&&&": {
                display: "flex",
                flexWrap: "nowrap",
                alignItems: "center",
                justifyContent: "flex-start",
                paddingLeft: unit(vars.searchIcon.gap),
            },
        },
        ...debug.name("valueContainer"),
    });

    // Has a search button attached.
    const compoundValueContainer = style(debug.name("compoundValueContainer"), {
        borderTopRightRadius: 0,
        borderBottomRightRadius: 0,
    });

    const actionButton = style({
        marginLeft: "auto",
        marginRight: -(globalVars.buttonIcon.offset + 3), // the "3" is to offset the pencil
        opacity: 0.8,
        $nest: {
            "&:hover": {
                opacity: 1,
            },
        },
        ...debug.name("actionButton"),
    });

    const label = style({
        display: "flex",
        alignItems: "center",
        justifyContent: "flex-start",
        ...debug.name("label"),
    });

    const clear = style({
        position: "relative",
        display: "flex",
        boxSizing: "border-box",
        height: unit(vars.sizing.height),
        width: unit(vars.sizing.height),
        color: vanillaHeaderVars.colors.fg.toString(),
        ...debug.name("clear"),
    });

    const form = style({
        display: "block",
        ...debug.name("form"),
    });

    const content = style({
        display: "flex",
        alignItems: "flex-start",
        justifyContent: "flex-start",
        position: "relative",
        minHeight: unit(vars.sizing.height),
        ...debug.name("content"),
    });

    // special selector
    const heading = style({
        $nest: {
            "&&": {
                marginBottom: unit(vars.heading.margin),
            },
        },
        ...debug.name("heading"),
    });

    const iconContainer = style({
        position: "absolute",
        top: 0,
        bottom: 0,
        left: "2px",
        height: percent(100),
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        width: unit(vars.searchIcon.gap),
        ...debug.name("iconContainer"),
    });
    const icon = style({
        width: unit(vars.searchIcon.width),
        height: unit(vars.searchIcon.height),
        ...debug.name("icon"),
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
