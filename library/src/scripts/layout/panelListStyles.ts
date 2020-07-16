/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { percent } from "csx";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { allLinkStates, unit } from "@library/styles/styleHelpers";

export const panelListVariables = useThemeCache(() => {
    const globalVals = globalVariables();
    const makeThemeVars = variableFactory("panelList");

    const title = makeThemeVars("title", {
        fontSize: globalVals.fonts.size.large,
    });
    const offset = makeThemeVars("offset", {
        default: 12,
    });
    const link = makeThemeVars("link", {
        fontSize: globalVals.fonts.size.medium,
        hover: {
            color: globalVals.links.colors.default,
        },
        focus: {
            color: globalVals.links.colors.focus,
        },
    });
    return {
        title,
        offset,
        link,
    };
});

export const panelListClasses = useThemeCache(mediaQueries => {
    const globalVars = globalVariables();
    const vars = panelListVariables();
    const style = styleFactory("panelList");

    const root = style({
        position: "relative",
        display: "block",
    });

    const title = style("title", {
        fontSize: unit(vars.title.fontSize),
        marginBottom: unit(vars.offset.default),
    });

    const item = style("item", {
        $nest: {
            "& + &": {
                marginTop: unit(vars.offset.default),
            },
        },
    });

    const link = style("link", {
        display: "block",
        position: "relative",
        width: percent(100),
        fontSize: unit(vars.link.fontSize),
        color: "inherit",
        ...allLinkStates({
            allStates: {
                textDecoration: "none",
            },
            hover: {
                color: globalVars.links.colors.hover,
            },
            focus: {
                color: globalVars.links.colors.focus,
            },
        }),
    });

    const items = style("items", {});

    return {
        root: root + " panelList", // This needs to be referenced in another file and was causing a circular import, so the static class is targetted instead
        title,
        item,
        link,
        items,
    };
});
