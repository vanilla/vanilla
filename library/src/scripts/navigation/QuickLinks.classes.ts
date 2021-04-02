/**
 * @author Alex Brohman <alex.brohman@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { CSSObject } from "@emotion/css";
import { quickLinksVariables } from "@library/navigation/QuickLinks.variables";
import { ListSeparation } from "@library/styles/cssUtilsTypes";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { colorOut, singleBorder } from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache } from "@library/styles/styleUtils";

export const quickLinksClasses = useThemeCache(() => {
    const vars = quickLinksVariables();
    const style = styleFactory("quickLinks");
    const globals = globalVariables();

    const root = style({
        border: "none",
    });

    const list = style("list", {});

    const { listSeparation } = vars.listItem;

    const listItem = style(
        "listItem",
        {
            width: "100%",
            display: "flex",
            alignItems: "center",
            ...Mixins.padding(vars.listItem.spacing),
        },
        listSeparation === ListSeparation.SEPARATOR && {
            borderBottom: singleBorder({
                width: vars.listItem.listSeparationWidth,
                color: vars.listItem.listSeparationColor,
            }),
            "&:last-child": {
                borderBottom: "none",
            },
        },
    );

    const listItemTitle = style(
        "listItemTitle",
        {
            width: "100%",
            display: "flex",
            justifyContent: "space-between",
            alignItems: "center",
            ...Mixins.padding(vars.listItem.padding),
            ...Mixins.font(vars.listItemTitle.font),
            ...Mixins.linkDecoration(),
        },
        listSeparation === ListSeparation.BORDER &&
            Mixins.border({
                width: vars.listItem.listSeparationWidth,
                color: vars.listItem.listSeparationColor,
            }),
    );

    const count = style("count", {
        whiteSpace: "nowrap", //Prevents count value from stacking.
        textAlign: "right",
        ...Mixins.font(vars.count.font),
    });

    return {
        root,
        list,
        listItem,
        listItemTitle,
        count,
    };
});
