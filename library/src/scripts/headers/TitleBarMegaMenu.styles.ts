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
        overflowY: "auto",
        maxHeight: `60vh`,
        display: "flex",
        flexDirection: "row",
        flexWrap: "wrap",
        paddingBottom: 20,
        ...Mixins.margin({
            //horizontal: -vars.spacing.menuItemSpacer,
        }),
    });

    const menuItem = style("menuItem", {
        ...Mixins.padding({
            horizontal: vars.spacing.menuItemSpacer,
            top: 20,
        }),
        flex: 1,
        flexBasis: 160,
    });

    const fillerItem = style("fillerItem", {
        flex: 1,
        flexBasis: 160,
    });

    const menuItemTitle = style("menuItemTitle", {
        display: "block",
        fontWeight: globalVars.fonts.weights.bold,
        fontSize: styleUnit(globalVars.fonts.size.medium),
        lineHeight: styleUnit(`${globalVars.fonts.size.medium * 1.25}`),
        marginBottom: styleUnit(12),
        color: ColorsUtils.colorOut(vars.colors.fg),
    });

    const menuItemChild = style("menuItemChild", {
        fontSize: styleUnit(14),
        lineHeight: styleUnit(`${globalVars.fonts.size.medium * 1.25}`),
        marginBottom: styleUnit(12),
        listStyle: "none",
        ...{
            a: {
                color: ColorsUtils.colorOut(vars.colors.fg),
            },

            "a:hover": {
                color: ColorsUtils.colorOut(globalVars.links.colors.hover),
            },
        },
    });

    return {
        wrapper,
        menuItem,
        fillerItem,
        container,
        menuItemTitle,
        menuItemChild,
    };
});
