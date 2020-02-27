/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory, useThemeCache } from "@library/styles/styleUtils";
import { color, percent } from "csx";
import { fonts, IFont } from "@library/styles/styleHelpersTypography";
import { colorOut, margins, negativeUnit, paddings, unit } from "@library/styles/styleHelpers";

export const themeBuilderVariables = () => {
    // Intentionally not overwritable with theming system.
    const fontFamily = ["Open Sans"];
    const textColor = color("#48576a");
    return {
        outline: {
            color: color("#0291db"),
        },
        width: 160,
        label: {
            color: textColor,
            size: 13,
            family: fontFamily,
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
        errorMessage: {
            family: fontFamily,
            color: color("#d0021b"),
            size: 12,
            weight: 600,
        },
        border: {
            color: color("#bfcbd8"),
            width: 1,
            style: "solid",
        },
        wrap: {
            borderRadius: 3,
        },
        error: {
            color: color("#d0021b"),
            backgroundColor: color("#FFF3D4"),
        },
        input: {
            height: 28,
        },
        panel: {
            bg: color("#f5f6f7"),
            padding: 16,
        },
        font: {
            color: color("#3c4146"),
            family: fontFamily,
        },
    };
};

export const themeBuilderClasses = useThemeCache(() => {
    const style = styleFactory("themeBuilder");
    const vars = themeBuilderVariables();
    // const editorVariables = themeEditorVariables();

    const root = style({
        backgroundColor: colorOut(vars.panel.bg),
        minHeight: percent(100),
        paddingTop: unit(vars.panel.padding),
        fontFamily: vars.label.family,
    });

    const inputBlock = style("inputBlock", {
        display: "flex",
        alignItems: "flex-start",
        justifyContent: "space-between",
        marginBottom: unit(8),
        ...paddings({
            horizontal: unit(vars.panel.padding),
        }),
    });

    const label = style("label", {
        width: unit(vars.width),
        display: "flex",
        alignItems: "center",
        flexBasis: unit(vars.width),
        flexGrow: 1,
        fontWeight: 600,
        height: unit(vars.input.height),
        ...fonts(vars.label),
    });

    const undoWrap = style("undoWrap", {
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        width: unit(24),
        height: unit(vars.input.height),
        flexBasis: unit(24),
    });

    const inputWrap = style("inputWrap", {
        display: "flex",
        flexWrap: "wrap",
        alignItems: "stretch",
        width: unit(vars.width),
        flexBasis: unit(vars.width),
    });

    const title = style("title", {
        ...fonts(vars.title),
        textAlign: "center",
        marginBottom: unit(20),
        ...paddings({
            horizontal: unit(vars.panel.padding),
        }),
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
        ...paddings({
            horizontal: unit(vars.panel.padding),
        }),
    });

    const subGroupSection = style("subGroupSection", {});
    const subGroupSectionTitle = style("subGroupSectionTitle", {
        ...fonts(vars.sectionGroupTitle),
        ...margins({
            top: 20,
            bottom: 12,
        }),
        ...paddings({
            horizontal: unit(vars.panel.padding),
        }),
    });

    const errorContainer = style("errorContainer", {
        flexGrow: 1,
        width: percent(100),
        display: "block",
        marginTop: negativeUnit(2),
    });

    const error = style("error", {
        width: percent(100),
        display: "block",
        ...fonts(vars.errorMessage),
        ...margins({
            vertical: 4,
        }),
    });

    return {
        root,
        inputBlock,
        label,
        undoWrap,
        inputWrap,
        title,
        section,
        sectionTitle,
        error,
        errorContainer,
        subGroupSection,
        subGroupSectionTitle,
    };
});
