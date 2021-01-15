/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { globalVariables } from "@library/styles/globalStyleVars";
import { titleBarVariables } from "@library/headers/TitleBar.variables";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { em, percent, px } from "csx";
import { Mixins } from "@library/styles/Mixins";

export const actionBarClasses = useThemeCache(() => {
    const style = styleFactory("actionBar");
    const titleBarVars = titleBarVariables();
    const mediaQueries = layoutVariables().mediaQueries();
    const globalVars = globalVariables();

    const items = style(
        "items",
        {
            display: "flex",
            flexWrap: "nowrap",
            justifyContent: "flex-end",
            alignItems: "center",
            width: percent(100),
            height: styleUnit(titleBarVars.sizing.height),
            listStyle: "none",
            margin: 0,
        },
        mediaQueries.oneColumnDown({
            height: styleUnit(titleBarVars.sizing.mobile.height),
        }),
    );

    const item = style("item", {
        display: "inline-flex",
        alignItems: "center",
        justifyContent: "center",
        ...{
            "&.isPullLeft": {
                ...Mixins.margin({
                    left: 0,
                    right: "auto",
                }),
                justifyContent: "flex-start",
            },
            "&.isPullRight": {
                ...Mixins.margin({
                    left: "auto",
                    right: 0,
                }),
                justifyContent: "flex-end",
                ...{
                    "& button": {
                        fontSize: globalVars.fonts.size.medium,
                    },
                },
            },
        },
    });

    const itemMarginLeft = style("itemMarginLeft", {
        marginLeft: styleUnit(globalVars.gutter.half),
        ...{
            "& button": {
                fontSize: globalVars.fonts.size.medium,
            },
        },
    });

    const centreColumn = style("centreColumn", {
        flexGrow: 1,
        ...Mixins.margin({
            horizontal: styleUnit(globalVars.spacer.size),
        }),
    });

    const callToAction = style("callToAction", {
        color: ColorsUtils.colorOut(globalVars.mainColors.primary),
        fontWeight: globalVars.fonts.weights.semiBold,
        whiteSpace: "nowrap",
    });

    const split = style("split", {
        flexGrow: 1,
        height: px(1),
    });

    const backLink = style("backLink", {
        ...{
            "&&": {
                marginRight: "auto",
                ...{
                    "& a": {
                        textDecoration: "none",
                    },
                },
            },
        },
    });

    const backSpacer = style("backSpacer", {
        position: "relative",
        visibility: "hidden",
    });

    const fullWidth = style("fullWidth", {
        boxSizing: "border-box",
        display: "flex",
        flexDirection: "column",
        marginLeft: "auto",
        marginRight: "auto",
        paddingLeft: "65px",
        paddingRight: "100px",
        position: "relative",
        width: "100%",
    });

    const anotherCallToAction = style("anotherCallToAction", {
        paddingRight: styleUnit(10),
    });

    return {
        items,
        centreColumn,
        item,
        split,
        backLink,
        itemMarginLeft,
        backSpacer,
        callToAction,
        fullWidth,
        anotherCallToAction,
    };
});
