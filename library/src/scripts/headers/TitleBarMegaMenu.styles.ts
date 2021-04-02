/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { Mixins } from "@library/styles/Mixins";
import { globalVariables } from "@library/styles/globalStyleVars";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { IThemeVariables } from "@library/theming/themeReducer";

export const titleBarMegaMenuVariables = useThemeCache((forcedVars?: IThemeVariables) => {
    const makeThemeVars = variableFactory("titleBarMegaMenu", forcedVars);

    const options = makeThemeVars("options", {});

    const colors = makeThemeVars("colors", {
        fg: "#555a62",
        bg: "#fff",
    });

    const wrapper = makeThemeVars("wrapper", {
        shadow: "0 5px 5px 0 rgba(0, 0, 0, 0.3)",
        backgroundColor: colors.bg,
    });

    const spacing = makeThemeVars("spacing", {
        menuItemSpacer: styleUnit(10),
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
        backgroundColor: vars.wrapper.backgroundColor,
        boxShadow: vars.wrapper.shadow,
        overflow: "hidden",
        transition: "height 200ms",
    });

    const container = style("container", {
        "&&": {
            overflowY: "auto",
            maxHeight: `60vh`,
            display: "flex",
            flexDirection: "row",
            flexWrap: "wrap",
            paddingBottom: 20,
        },
    });

    const menuItem = style("menuItem", {
        minWidth: 160,
        maxWidth: 300,
        ...Mixins.padding({
            horizontal: vars.spacing.menuItemSpacer,
            top: 20,
        }),
    });

    const menuItemChildren = style("menuItemChildren", {
        // you may be tempted to put a flex-column w/ wrapping on this.
        // As of 2021 it doesn't actually work.
        // Wrapping columns in flex don't extend the width of the container.
        // @see https://bugs.chromium.org/p/chromium/issues/detail?id=507397
        //      "This is unlikely to be fixed in 2021. See comment 40 for brief description of the implementation
        //       difficulty, on top of the difficult compat situation."
        // @see https://i.stack.imgur.com/Es3ch.gif
        // Maybe in the future some JS solution would work?
    });

    const fillerItem = style("fillerItem", {
        flex: 1,
        flexBasis: 160,
    });

    const menuItemTitle = style("menuItemTitle", {
        display: "block",
        ...Mixins.font({
            ...globalVars.fontSizeAndWeightVars("medium", "bold"),
            lineHeight: styleUnit(`${globalVars.fonts.size.medium * 1.25}`), // FIXME: see if this is necessary
            color: ColorsUtils.colorOut(vars.colors.fg),
        }),
        marginBottom: styleUnit(12),
    });

    const menuItemChild = style("menuItemChild", {
        fontSize: styleUnit(14),
        lineHeight: styleUnit(`${globalVars.fonts.size.medium * 1.25}`), // FIXME: see if this is necessary
        listStyle: "none",
        marginBottom: styleUnit(12),
        "&:last-child": {
            marginBottom: 0,
        },

        "& a": {
            color: ColorsUtils.colorOut(vars.colors.fg),
        },

        "& a:hover": {
            color: ColorsUtils.colorOut(globalVars.links.colors.hover),
        },
    });

    return {
        wrapper,
        menuItem,
        menuItemChildren,
        fillerItem,
        container,
        menuItemTitle,
        menuItemChild,
    };
});
