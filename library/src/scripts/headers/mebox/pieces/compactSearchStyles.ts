/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { colorOut, unit } from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { percent, px } from "csx";
import { titleBarVariables } from "@library/headers/titleBarStyles";
import { searchBarClasses } from "@library/features/search/searchBarStyles";
import { layoutVariables } from "@library/layout/panelLayoutStyles";

export const compactSearchVariables = useThemeCache(() => {
    const makeVars = variableFactory("compactSearch");
    const titleBarVars = titleBarVariables();

    const baseColor = titleBarVars.colors.bg.darken(0.05);
    const colors = makeVars("colors", {
        bg: baseColor.fadeOut(0.8),
        fg: titleBarVars.colors.fg,
        placeholder: titleBarVars.colors.fg.fade(0.8),
        active: {
            bg: baseColor,
        },
    });

    return { colors };
});
export const compactSearchClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const formElementsVars = formElementsVariables();
    const titleBarVars = titleBarVariables();
    const vars = compactSearchVariables();
    const style = styleFactory("compactSearch");
    const mediaQueries = layoutVariables().mediaQueries();

    const root = style({
        $nest: {
            ".searchBar": {
                flexGrow: 1,
            },
            "& .searchBar__input": {
                color: colorOut(vars.colors.fg),
                width: percent(100),
            },
            ".searchBar-valueContainer": {
                height: unit(formElementsVars.sizing.height),
                backgroundColor: colorOut(vars.colors.bg),
                border: 0,
            },
            ".hasFocus .searchBar-valueContainer": {
                backgroundColor: colorOut(vars.colors.active.bg),
            },
            ".searchBar__placeholder": {
                color: colorOut(vars.colors.placeholder),
            },
            ".searchBar-icon": {
                color: colorOut(vars.colors.placeholder),
            },
            "&.isOpen": {
                maxWidth: percent(100),
            },
            "&.isCentered": {
                margin: "auto",
            },
            ".suggestedTextInput-inputText": {
                borderTopRightRadius: unit(globalVars.border.radius),
                borderBottomRightRadius: unit(globalVars.border.radius),
            },
        },
    });

    const contents = style(
        "contents",
        {
            display: "flex",
            alignItems: "center",
            flexWrap: "nowrap",
            minHeight: unit(formElementsVars.sizing.height),
            justifyContent: "center",
            width: percent(100),
            position: "relative",
        },
        mediaQueries.oneColumnDown({
            height: unit(titleBarVars.sizing.mobile.height),
        }),
    );

    const close = style("close", {
        color: "inherit",
        whiteSpace: "nowrap",
        fontWeight: globalVars.fonts.weights.semiBold,
    });

    const cancelContents = style("cancelContents", {
        padding: px(4),
    });

    const searchAndResults = style("searchAndResults", {
        flex: 1,
        position: "relative",
        width: percent(100),
        height: unit(formElementsVars.sizing.height),
        display: "flex",
        flexWrap: "nowrap",
    });

    return {
        root,
        contents,
        close,
        cancelContents,
        searchAndResults,
    };
});
