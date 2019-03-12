/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { useThemeCache, variableFactory, styleFactory } from "@library/styles/styleUtils";
import { NestedCSSProperties, NestedCSSSelectors } from "typestyle/lib/types";
import { margins } from "@library/styles/styleHelpers";
import { em } from "csx";
import { lineHeightAdjustment } from "@library/styles/textUtils";

const userContentVariables = useThemeCache(() => {
    const makeThemeVars = variableFactory("userContent");
    const globalVars = globalVariables();

    const fonts = makeThemeVars("fonts", {
        size: globalVars.fonts.size.medium,
        headings: {
            h1: "2em",
            h2: "1.5em",
            h3: "1.25em",
            h4: "1em",
            h5: ".875em",
            h6: ".85em",
        },
    });

    const list = makeThemeVars("list", {
        spacing: {
            top: em(0.5),
            left: em(2),
        },
        listDecoration: {
            minWidth: em(2),
        },
    });

    return { fonts, list };
});

/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
export const userContentStyles = useThemeCache(() => {
    const style = styleFactory("userContent");
    const vars = userContentVariables();
    const globalVars = globalVariables();

    const listItem: NestedCSSProperties = {
        position: "relative",
        ...margins({
            top: vars.list.spacing.top,
            left: vars.list.spacing.left,
        }),
        $nest: {
            "> &:first-child": {
                marginTop: 0,
            },
            "> &:last-child": {
                marginBottom: 0,
            },
        },
    };

    const headings: NestedCSSSelectors = {
        "& h1, & h2, & h3, & h4, & h5, & h6": {
            $nest: lineHeightAdjustment(globalVars.lineHeights.condensed),
        },
    };

    const lists: NestedCSSSelectors = {
        "& ol": {
            listStylePosition: "inside",
        },
        "& ol li": {
            ...listItem,
            listStyle: "decimal",
        },
        "& ul li": {
            ...listItem,
            listStyle: "initial",
        },
    };

    const root = style({
        $nest: {
            ...headings,
            ...lists,
        },
    });

    return { root };
});
