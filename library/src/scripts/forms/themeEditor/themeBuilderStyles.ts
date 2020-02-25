/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { color } from "csx";
import { fonts, IFont } from "@library/styles/styleHelpersTypography";
import { borders, margins, unit } from "@library/styles/styleHelpers";

export const themeBuilderVariables = () => {
    // Intentionally not overwritable with theming system.
    const fontFamily = ["Open Sans"];
    const textColor = color("#48576a");
    return {
        outline: {
            color: color("#0291db"),
            warning: color("#ffebed"),
        },
        width: 160,
        label: {
            family: fontFamily,
            color: textColor,
            size: 13,
        },
        title: {
            family: fontFamily,
            color: textColor,
            size: 16,
            weight: 700,
            transform: "uppercase",
        } as IFont,
        sectionTitle: {
            family: fontFamily,
            color: color("#757e8c"),
            size: 12,
            transform: "uppercase",
            weight: 600,
        } as IFont,
        sectionGroupTitle: {
            family: fontFamily,
            color: color("#2d343f"),
            size: 14,
            weight: 700,
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
        marginBottom: unit(8),
    });

    const label = style("label", {
        width: unit(vars.width),
        flexBasis: unit(vars.width),
        flexGrow: 1,
        fontWeight: 600,
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
        marginBottom: unit(16),
    });

    const section = style("section", {
        borderTop: `solid #c1cbd7 1px`,
        marginTop: unit(32),
    });

    const sectionTitle = style("sectionTitle", {
        ...fonts(vars.sectionTitle),
        textAlign: "center",
        ...margins({
            top: 6,
            bottom: 14,
        }),
    });

    // const subSection = style("subSection", {});
    //
    // const subSectionTitle = style("subSectionTitle", {
    //
    // });

    const subGroupSection = style("subGroupSection", {});
    const subGroupSectionTitle = style("subGroupSectionTitle", {
        ...fonts(vars.sectionGroupTitle),
        ...margins({
            top: 20,
            bottom: 12,
        }),
    });

    return {
        inputBlock,
        label,
        undoWrap,
        inputWrap,
        title,
        section,
        sectionTitle,
        // subSection,
        // subSectionTitle,
        subGroupSection,
        subGroupSectionTitle,
    };
});
