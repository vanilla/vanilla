/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { colorOut, paddings, singleBorder, unit } from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache } from "@library/styles/styleUtils";
import { important } from "csx";
import { frameVariables } from "@library/layout/frame/frameStyles";

export const frameFooterClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const style = styleFactory("frameFooter");
    const vars = frameVariables();

    const root = style({
        display: "flex",
        minHeight: unit(vars.footer.minHeight),
        alignItems: "center",
        position: "relative",
        zIndex: 1,
        borderTop: singleBorder(),
        flexWrap: "wrap",
        justifyContent: "space-between",
        ...paddings({
            top: 0,
            bottom: 0,
            left: vars.footer.spacing,
            right: vars.footer.spacing,
        }),
    });

    const justifiedRight = style("justifiedRight", {
        $nest: {
            "&&": {
                justifyContent: "flex-end",
            },
        },
    });

    const markRead = style("markRead", {
        $nest: {
            "&.buttonAsText": {
                fontWeight: globalVars.fonts.weights.semiBold,
                color: colorOut(globalVars.mainColors.primary),
            },
        },
    });

    const actionButton = style("actionButton", {
        marginLeft: unit(24),
    });

    const selfPadded = style({
        paddingLeft: important(0),
        paddingRight: important(0),
    });

    return {
        root,
        markRead,
        selfPadded,
        actionButton,
        justifiedRight,
    };
});
