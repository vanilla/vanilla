/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { color } from "csx";
import { fonts } from "@library/styles/styleHelpersTypography";
import { borders, unit } from "@library/styles/styleHelpers";

export const themeBuilderVariables = () => {
    // Intentionally not overwritable with theming system.
    return {
        outline: {
            color: color("#0291db"),
            warning: color("#d0021b"),
        },
        width: 160,
        fonts: {
            family: ["Open Sans"],
            color: color("#48576a"),
            size: 13,
        },
        border: {
            color: color("#bfcbd8"),
            width: 1,
            style: "solid",
        },
        wrap: {
            borderRadius: 3,
        },
    };
};

export const themeBuilderClasses = useThemeCache(() => {
    const style = styleFactory("themeEditor");
    const vars = themeBuilderVariables();

    const inputBlock = style("inputBlock", {
        display: "flex",
        alignItems: "center",
        justifyContent: "space-between",
    });

    const label = style("label", {
        width: unit(vars.width),
        flexBasis: unit(vars.width),
        flexGrow: 1,
        ...fonts(vars.fonts),
    });

    const undoWrap = style("undoWrap", {
        display: "block",
        width: unit(24),
        height: unit(24),
        flexBasis: unit(24),
    });

    const inputWrap = style("inputWrap", {
        display: "flex",
        flexWrap: "nowrap",
        alignItems: "stretch",
        width: unit(vars.width),
        flexBasis: unit(vars.width),
    });

    return {
        inputBlock,
        label,
        undoWrap,
        inputWrap,
    };
});
