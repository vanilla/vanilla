/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { color, percent, px } from "csx";
import { globalVariables } from "@library/styles/globalStyleVars";
import { getColorDependantOnLightness } from "@library/styles/styleHelpers";
import { layoutStyles } from "@library/styles/layoutStyles";
import { style } from "typestyle";
import { formElementsVariables } from "@library/components/forms/formElementStyles";
import { userPhotoVariables } from "@library/styles/userPhotoStyles";
import { vanillaMenuVariables } from "@library/styles/vanillaMenu";
import { vanillaHeaderVariables } from "@library/components/headers/vanillaHeaderStyles";

export function vanillaHeaderNavigation() {
    const globalVars = globalVariables();

    const border = {
        verticalWidth: 3,
        active: {
            border: {
                color: globalVars.mainColors.bg.fade(90),
            },
        },
    };

    const active = {
        bottomOffset: 8,
    };

    const item = {
        size: 30,
    };

    return {
        border,
        active,
        item,
    };
}

export default function vanillaHeaderNavClasses() {
    const globalVars = globalVariables();
    const headerVars = vanillaHeaderVariables();
    const vars = vanillaHeaderNavigation();
    const mediaQueries = layoutStyles().mediaQueries();

    const root = style({
        position: "relative",
    });

    const navigation = style({
        display: "flex",
        alignItems: "center",
        flexWrap: "nowrap",
        height: percent(100),
        color: "inherit",
        $nest: {
            "&.isScrolled": {
                alignItems: "center",
                justifyContent: "center",
                flexWrap: "nowrap",
                minWidth: percent(100),
            },
        },
    });

    const items = style(
        {
            display: "flex",
            alignItems: "center",
            justifyContent: "center",
            flexWrap: "nowrap",
            height: px(vars.item.size),
            $nest: {
                "&.isCurrent": {
                    $nest: {
                        "&.vanillaHeaderNav-linkContent": {
                            $nest: {
                                "&:after": {
                                    marginLeft: -2,
                                    width: `calc(100% + 4px})`,
                                    borderBottomColor: vars.border.active.border.toString(),
                                },
                            },
                        },
                    },
                },
            },
        },
        mediaQueries.oneColumn({
            height: px(headerVars.sizing.mobile.height),
        }),
    );

    const link = style({
        display: "flex",
        justifyContent: "center",
        alignItems: "stretch",
        height: px(vars.item.size),
        $nest: {
            "&.focusVisible": {
                backgroundColor: headerVars.buttonContents.hover.bg.toString(),
            },
            "&:hover": {
                backgroundColor: headerVars.buttonContents.hover.bg.toString(),
            },
        },
    });

    const linkContent = style({
        position: "relative",
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        flexWrap: "nowrap",
        $nest: {
            "&:after": {
                content: "",
                position: "absolute",
                top: 0,
                right: 0,
                bottom: 0,
                left: 0,
                width: percent(100),
                marginBottom: -vars.active.bottomOffset,
                borderStyle: "solid",
                borderColor: "transparent",
                borderTopWidth: vars.border.verticalWidth,
                borderRightWidth: 0,
                borderBottomWidth: vars.border.verticalWidth,
                borderLeftWidth: 0,
            },
        },
    });

    return {
        root,
        navigation,
        items,
        link,
        linkContent,
    };
}
