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
    const fontFamily = ["Open Sans"];
    const textColor = color("#48576a");
    return {
        outline: {
            color: color("#0291db"),
            warning: color("#d0021b"),
        },
        width: 160,
        title: {
            family: fontFamily,
            color: textColor,
            size: 16,
            weight: 700,
        },
        label: {
            family: fontFamily,
            color: textColor,
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
        ...fonts(vars.label),
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

    const title = style("title", {
        ...fonts(vars.title),
        textAlign: "center",
        textTransform: "uppercase",
        marginBottom: unit(16),
    });

    const section = style("section", {});
    const sectionTitle = style("sectionTitle", {});
    const subSection = style("subSection", {});
    const subSectionTitle = style("subSectionTitle", {});
    const subGroupSection = style("subGroupSection", {});
    const subGroupSectionTitle = style("subGroupSectionTitle", {});

    return {
        inputBlock,
        label,
        undoWrap,
        inputWrap,
        title,
        section,
        sectionTitle,
        subSection,
        subSectionTitle,
        subGroupSection,
        subGroupSectionTitle,
    };
});
