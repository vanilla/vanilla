/**
 * @author Alex Brohman <alex.brohman@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { css } from "@emotion/css";
import { IHomeWidgetContainerOptions } from "@library/homeWidget/HomeWidgetContainer.styles";
import { quickLinksVariables } from "@library/navigation/QuickLinks.variables";
import { ListSeparation } from "@library/styles/cssUtilsTypes";
import { Mixins } from "@library/styles/Mixins";
import { singleBorder } from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache } from "@library/styles/styleUtils";

export const quickLinksClasses = useThemeCache((containerOptions?: IHomeWidgetContainerOptions) => {
    const vars = quickLinksVariables();
    const style = styleFactory("quickLinks");

    const root = style({
        border: "none",
        ...Mixins.background(containerOptions?.outerBackground ?? {}),
    });

    const list = style("list", { ...Mixins.margin(vars.list.spacing) });

    const { listSeparation } = vars.listItem;

    const listItem = style(
        "listItem",
        {
            width: "100%",
            ...Mixins.margin(vars.listItem.spacing),
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
        listSeparation === ListSeparation.BORDER &&
            Mixins.border({
                width: vars.listItem.listSeparationWidth,
                color: vars.listItem.listSeparationColor,
            }),
    );

    const link = (active = false) =>
        css({
            display: "flex",
            justifyContent: "space-between",
            alignItems: "center",
            flex: 1,
            ...Mixins.padding(vars.listItem.padding),
            ...Mixins.font(vars.listItem.font),
            ...(active ? Mixins.font(vars.listItem.fontState) : {}),
            "&:hover, &:focus, &:active, &.focus-visible": {
                ...Mixins.font(vars.listItem.fontState),
            },
        });

    const count = style("count", {
        whiteSpace: "nowrap", //Prevents count value from stacking.
        textAlign: "right",
        ...Mixins.font(vars.count.font),
    });

    return {
        root,
        list,
        listItem,
        link,
        count,
    };
});
