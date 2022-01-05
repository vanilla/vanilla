/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useThemeCache } from "@library/styles/themeCache";
import { globalVariables } from "@library/styles/globalStyleVars";
import { titleBarVariables } from "@library/headers/TitleBar.variables";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { oneColumnVariables } from "@library/layout/Section.variables";
import { percent, px } from "csx";
import { Mixins } from "@library/styles/Mixins";
import { css } from "@emotion/css";

export const actionBarClasses = useThemeCache(() => {
    const titleBarVars = titleBarVariables();
    const mediaQueries = oneColumnVariables().mediaQueries();
    const globalVars = globalVariables();

    const items = css(
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

    const item = css({
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
                        ...Mixins.font({
                            ...globalVars.fontSizeAndWeightVars("medium"),
                        }),
                    },
                },
            },
        },
    });

    const itemMarginLeft = css({
        marginLeft: styleUnit(globalVars.gutter.half),
        ...{
            "& button": {
                ...Mixins.font({
                    ...globalVars.fontSizeAndWeightVars("medium"),
                }),
            },
        },
    });

    const centreColumn = css({
        flexGrow: 1,
        ...Mixins.margin({
            horizontal: styleUnit(globalVars.spacer.size),
        }),
    });

    const callToAction = css({
        color: ColorsUtils.colorOut(globalVars.mainColors.primary),
        fontWeight: globalVars.fonts.weights.semiBold,
        whiteSpace: "nowrap",
    });

    const split = css({
        flexGrow: 1,
        height: px(1),
    });

    const backLink = css({
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

    const backSpacer = css({
        position: "relative",
        visibility: "hidden",
    });

    const fullWidth = css({
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

    const anotherCallToAction = css({
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
