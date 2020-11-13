/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { colorOut, paddings, spinnerLoader, unit } from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { IThemeVariables } from "@library/theming/themeReducer";
import { calc } from "csx";
import { titleBarNavigationVariables } from "@library/headers/titleBarNavStyles";

export const titleBarMegaMenuVariables = useThemeCache((forcedVars?: IThemeVariables) => {
    const makeThemeVars = variableFactory("titleBarMegaMenu", forcedVars);
    const globalVars = globalVariables();

    const options = makeThemeVars("options", {});

    const wrapper = makeThemeVars("wrapper", {
        shadow: "0 5px 5px 0 rgba(0, 0, 0, 0.3)",
    });

    const colors = makeThemeVars("colors", {
        fg: "#555a62",
        bg: "#fff",
    });

    const spacing = makeThemeVars("spacing", {
        menuItemSpacer: unit(10),
    });

    return {
        options,
        wrapper,
        colors,
        spacing,
    };
});

export default useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = titleBarMegaMenuVariables();
    const style = styleFactory("titleBarMegaMenu");

    const wrapper = style("wrapper", {
        position: "fixed",
        left: 0,
        right: 0,
        background: colorOut(vars.colors.bg),
        boxShadow: vars.wrapper.shadow,
        overflow: "hidden",
        transition: "height 200ms",
    });

    const container = style("container", {
        overflowY: "auto",
        maxHeight: `60vh`,
        display: "flex",
        flexDirection: "row",
        flexWrap: "wrap",
    });

    const menuItem = style("menuItem", {
        paddingTop: unit(20),
        paddingBottom: 0,
        paddingRight: vars.spacing.menuItemSpacer,
        paddingLeft: titleBarNavigationVariables().padding.horizontal * 3,
        width: calc(`20% - ${vars.spacing.menuItemSpacer}`),

        $nest: {
            "&:last-of-type": {
                paddingRight: 0,
            },
        },
    });

    const menuItemTitle = style("menuItemTitle", {
        display: "block",
        fontWeight: globalVars.fonts.weights.bold,
        fontSize: unit(globalVars.fonts.size.medium),
        lineHeight: unit(`${globalVars.fonts.size.medium * 1.25}`),
        marginBottom: unit(12),
        color: colorOut(vars.colors.fg),

        $nest: {
            "&.displayAsLink": {
                fontWeight: globalVars.fonts.weights.normal,
                color: colorOut(vars.colors.fg),
            },

            "&.displayAsLink:hover": {
                color: colorOut(globalVars.links.colors.hover),
            },
        },
    });

    const menuItemChild = style("menuItemChild", {
        fontSize: unit(14),
        lineHeight: unit(`${globalVars.fonts.size.medium * 1.25}`),
        marginBottom: unit(12),
        listStyle: "none",
        $nest: {
            a: {
                color: colorOut(vars.colors.fg),
            },

            "a:hover": {
                color: colorOut(globalVars.links.colors.hover),
            },

            "&:last-of-type": {
                paddingBottom: unit(20),
            },
        },
    });

    return {
        wrapper,
        menuItem,
        container,
        menuItemTitle,
        menuItemChild,
    };
});
