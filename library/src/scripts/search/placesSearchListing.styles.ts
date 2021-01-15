/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { important } from "csx";
import { Mixins } from "@library/styles/Mixins";

export const placesSearchListingVariables = useThemeCache(() => {
    const makeThemeVars = variableFactory("placesSearchListing");
    const globalVars = globalVariables();

    const colors = makeThemeVars("color", {
        fg: globalVars.meta.text.color,
        hover: {
            fg: globalVars.links.colors.active,
        },
        focus: {
            fg: globalVars.links.colors.active,
        },
        active: {
            fg: globalVars.links.colors.active,
        },
        deleted: globalVars.messageColors.deleted,
    });

    const iconSize = makeThemeVars("iconSize", {
        size: {
            width: globalVars.icon.sizes.small,
            height: globalVars.icon.sizes.small,
        },
    });
    return {
        iconSize,
        colors,
    };
});

export const placesSearchListingClasses = useThemeCache(() => {
    const style = styleFactory("placesSearchListing");
    const globalVars = globalVariables();
    const vars = placesSearchListingVariables();
    const linkColors = Mixins.clickable.itemState();

    const container = style("container", {
        display: "flex",
        alignItems: "center",
        justifyContent: "flex-start",
        flexWrap: "wrap",
    });

    const buttonIconContainer = style("buttonIconContainer", {
        display: "flex",
        alignItems: "center",
        ...vars.iconSize.size,
    });

    const link = style("link", {
        fontSize: important(globalVars.fonts.size.small),
        display: important("flex"),
        alignItems: important("center"),
        color: ColorsUtils.colorOut(vars.colors.fg),
        marginRight: styleUnit(24),
        padding: "6px 0",
        ...linkColors,
    });

    return {
        container,
        buttonIconContainer,
        link,
    };
});
