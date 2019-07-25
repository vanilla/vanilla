/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { globalVariables } from "@library/styles/globalStyleVars";
import { debugHelper, unit } from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache } from "@library/styles/styleUtils";
import { percent } from "csx";

export const dateRangeClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const style = styleFactory("dateRange");

    const root = style({
        display: "block",
        position: "relative",
        width: percent(100),
    });

    const boundary = style("boundary", {
        display: "flex",
        flexWrap: "wrap",
        alignItems: "center",
        justifyContent: "space-between",
        width: percent(100),
        $nest: {
            "& + &": {
                marginTop: unit(12),
            },
        },
    });

    const label = style("label", {
        overflow: "hidden",
        fontWeight: globalVars.fonts.weights.semiBold,
        wordBreak: "break-word",
        textOverflow: "ellipsis",
        maxWidth: percent(100),
        paddingLeft: unit(8),
    });

    const input = style("input", {
        width: unit(136),
        maxWidth: percent(100),
    });

    return {
        root,
        boundary,
        label,
        input,
    };
});
