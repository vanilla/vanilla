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
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("panelList");

    const title = makeThemeVars("title", {
        fontSize: globalVars.fonts.size.large,
    });
    const offset = makeThemeVars("offset", {
        default: 12,
    });
    const link = makeThemeVars("link", {
        fontSize: globalVars.fonts.size.medium,
        hover: {
            color: globalVars.links.colors.default,
        },
        focus: {
            color: globalVars.links.colors.focus,
        },
    });
    return {
        title,
        offset,
        link,
    };
});

export const panelListClasses = useThemeCache((props: { mediaQueries }) => {
    const globalVars = globalVariables();
    const panelVars = panelListVariables();
    const style = styleFactory("panelList");

    const root = style({
        position: "relative",
        display: "block",
    });

    const title = style("title", {
        fontSize: unit(panelVars.title.fontSize),
        marginBottom: unit(panelVars.offset.default),
    });

    const item = style("item", {
        $nest: {
            "& + &": {
                marginTop: unit(panelVars.offset.default),
            },
        },
    });

    const link = style("link", {
        display: "block",
        position: "relative",
        width: percent(100),
        fontSize: unit(panelVars.link.fontSize),
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
        root,
        title,
        item,
        link,
        items,
    };
});
