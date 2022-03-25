/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { Mixins } from "@library/styles/Mixins";
import { globalVariables } from "@library/styles/globalStyleVars";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { IThemeVariables } from "@library/theming/themeReducer";
import { Variables } from "@library/styles/Variables";
import { LocalVariableMapping } from "@library/styles/VariableMapping";
import { css } from "@emotion/css";

/**
 * @varGroup titleBarMegaMenu
 * @title Mega Menu (Title Bar)
 * @description The mega menu is used when you have navigation items that are nested inside of the titlebar on desktop screen sizes.
 */
export const titleBarMegaMenuVariables = useThemeCache((forcedVars?: IThemeVariables) => {
    const globalVars = globalVariables();

    const makeThemeVars = variableFactory("titleBarMegaMenu", forcedVars, [
        new LocalVariableMapping({
            "item.spacer": "spacing.menuItemSpacer",
            "item.font.color": "colors.fg",
            "wrapper.background.color": "colors.bg",
        }),
    ]);

    /**
     * @varGroup titleBarMegaMenu.item
     * @description Item is used to set menu item font properties and spacing
     */
    const item = makeThemeVars("item", {
        /**
         * @var titleBarMegaMenu.item.spacer
         * @description Spacing units for menu item
         * @title Content Spacing
         * @type number
         */
        spacer: 10,
        /**
         * @varGroup titleBarMegaMenu.item.font
         * @expand font
         */
        font: Variables.font({
            size: 14,
            color: globalVars.mainColors.fg,
        }),
    });

    /**
     * @varGroup titleBarMegaMenu.title
     * @description Title is used to set the font properties of the menu items that have children
     */
    const title = makeThemeVars("title", {
        /**
         * @varGroup titleBarMegaMenu.title.font
         * @expand font
         */
        font: Variables.font({
            ...globalVars.fontSizeAndWeightVars("medium", "bold"),
            color: item.font.color,
            lineHeight: 1.25,
        }),
    });

    /**
     * @varGroup titleBarMegaMenu.wrapper
     * @description Wrapper is used to set the themed background and shadow of the mega menu
     */
    const wrapper = makeThemeVars("wrapper", {
        /**
         * @var titleBarMegaMenu.wrapper.shadow
         * @description Shadow detail at the bottom of the mega menu
         * @title Shadow
         * @type string
         */
        shadow: `0 5px 5px 0 ${ColorsUtils.colorOut(globalVars.elementaryColors.black.fade(0.3))}`,
        /**
         * @varGroup titleBarMegaMenu.wrapper.background
         * @expand background
         */
        background: Variables.background({
            color: globalVars.mainColors.bg,
        }),
    });

    return {
        title,
        wrapper,
        item,
    };
});

export default useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = titleBarMegaMenuVariables();

    const wrapper = css({
        position: "fixed",
        left: 0,
        right: 0,
        ...Mixins.background(vars.wrapper.background),
        boxShadow: vars.wrapper.shadow,
        overflow: "hidden",
        transition: "height 200ms",
    });

    const container = css({
        "&&": {
            overflowY: "auto",
            maxHeight: `60vh`,
            display: "flex",
            flexDirection: "row",
            flexWrap: "wrap",
            paddingBottom: 20,
        },
    });

    const menuItem = css({
        minWidth: 160,
        maxWidth: 300,
        ...Mixins.padding({
            horizontal: vars.item.spacer,
            top: 20,
        }),
    });

    const menuItemChildren = css({
        // you may be tempted to put a flex-column w/ wrapping on this.
        // As of 2021 it doesn't actually work.
        // Wrapping columns in flex don't extend the width of the container.
        // @see https://bugs.chromium.org/p/chromium/issues/detail?id=507397
        //      "This is unlikely to be fixed in 2021. See comment 40 for brief description of the implementation
        //       difficulty, on top of the difficult compat situation."
        // @see https://i.stack.imgur.com/Es3ch.gif
        // Maybe in the future some JS solution would work?
    });

    const fillerItem = css({
        flex: 1,
        flexBasis: 160,
    });

    const menuItemTitle = css({
        display: "block",
        ...Mixins.font(vars.title.font),
        marginBottom: styleUnit(12),
    });

    const menuItemChild = css({
        fontSize: styleUnit(14),
        lineHeight: 1.25,
        listStyle: "none",
        marginBottom: styleUnit(12),
        "&:last-child": {
            marginBottom: 0,
        },

        "& a": {
            ...Mixins.font(vars.item.font),
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
