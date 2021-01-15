/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { singleBorder } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { important } from "csx";
import { frameVariables } from "@library/layout/frame/frameStyles";
import { Mixins } from "@library/styles/Mixins";

export const frameFooterClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const style = styleFactory("frameFooter");
    const vars = frameVariables();

    const root = style({
        display: "flex",
        minHeight: styleUnit(vars.footer.minHeight),
        alignItems: "center",
        position: "relative",
        zIndex: 1,
        borderTop: singleBorder(),
        flexWrap: "wrap",
        justifyContent: "space-between",
        ...Mixins.padding({
            top: 0,
            bottom: 0,
            left: vars.footer.spacing,
            right: vars.footer.spacing,
        }),
    });

    const justifiedRight = style("justifiedRight", {
        ...{
            "&&": {
                justifyContent: "flex-end",
            },
        },
    });

    const markRead = style("markRead", {
        ...{
            "&.buttonAsText": {
                fontWeight: globalVars.fonts.weights.semiBold,
                color: ColorsUtils.colorOut(globalVars.mainColors.primary),
            },
        },
    });

    const actionButton = style("actionButton", {
        marginLeft: styleUnit(24),
    });

    const selfPadded = style({
        paddingLeft: important(0),
        paddingRight: important(0),
    });

    const forDashboard = style({
        minHeight: styleUnit(65),
    });

    return {
        root,
        markRead,
        selfPadded,
        actionButton,
        justifiedRight,
        forDashboard,
    };
});
