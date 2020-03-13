/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory, useThemeCache } from "@library/styles/styleUtils";
import { color, percent } from "csx";
import { fonts } from "@library/styles/styleHelpersTypography";
import { colorOut, margins, negativeUnit, paddings, unit } from "@library/styles/styleHelpers";
import { TextTransformProperty } from "csstype";

export const themeBuilderVariables = () => {
    // Intentionally not overwritable with theming system.
    const fontFamily = ["Open Sans"];

    const mainColors = {
        primary: color("#0291db"),
        fg: color("#48576a"),
        bg: color("#f5f6f7"),
    };

    const outline = {
        color: mainColors.primary,
    };

    const defaultFont = {
        color: mainColors.fg,
        family: fontFamily,
    };

    const label = {
        width: 160,
        fonts: {
            ...defaultFont,
            size: 13,
            weight: 600,
        },
    };
    const title = {
        fonts: {
            ...defaultFont,
            size: 16,
            weight: 700,
            transform: "uppercase" as TextTransformProperty,
        },
    };
    const sectionTitle = {
        fonts: {
            family: fontFamily,
            color: color("#757e8c"),
            size: 12,
            transform: "uppercase" as TextTransformProperty,
            weight: 600,
        },
    };
    const sectionGroupTitle = {
        fonts: {
            family: fontFamily,
            color: color("#2d343f"),
            size: 14,
            weight: 700,
        },
    };
    const errorMessage = {
        fonts: {
            family: fontFamily,
            color: color("#d0021b"),
            size: 12,
            weight: 600,
        },
    };
    const border = {
        color: color("#bfcbd8"),
        width: 1,
        style: "solid",
    };
    const wrap = {
        borderRadius: 3,
    };
    const error = {
        color: color("#d0021b"),
        backgroundColor: color("#FFF3D4"),
    };
    const undo = {
        width: 24,
    };

    const panel = {
        bg: mainColors.bg,
        padding: 16,
        width: themeEditorVariables().panel.width,
    };
    const input = {
        height: 29, // Odd to allow perfect centring of spinner buttons.
        width: panel.width - 2 * panel.padding - undo.width - label.width,
        fonts: {
            ...defaultFont,
            size: 13,
        },
    };

    return {
        mainColors,
        defaultFont,
        outline,
        label,
        title,
        sectionTitle,
        sectionGroupTitle,
        errorMessage,
        error,
        border,
        wrap,
        input,
        panel,
    };
};

// Intentionally not overwritable.
export const themeEditorVariables = () => {
    const frame = {
        width: 100,
    };

    const panel = {
        width: 376,
    };

    return {
        frame,
        panel,
    };
};

export const themeBuilderClasses = useThemeCache(() => {
    const style = styleFactory("themeBuilder");
    const vars = themeBuilderVariables();

    const root = style({
        maxWidth: unit(themeEditorVariables().panel.width),
        backgroundColor: colorOut(vars.panel.bg),
        minHeight: percent(100),
        ...fonts(vars.defaultFont),
        ...paddings({
            vertical: unit(vars.panel.padding),
        }),
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
        width: unit(vars.label.width),
        display: "flex",
        alignItems: "center",
        flexBasis: unit(vars.label.width),
        flexGrow: 1,
        fontWeight: 600,
        height: unit(vars.input.height),
        ...fonts(vars.label.fonts),
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
        justifyContent: "flex-end",
        width: unit(vars.input.width),
        flexBasis: unit(vars.input.width),
    });

    const title = style("title", {
        ...fonts(vars.title.fonts),
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
        ...fonts(vars.sectionTitle.fonts),
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
        ...fonts(vars.sectionGroupTitle.fonts),
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
        ...fonts(vars.errorMessage.fonts),
        ...margins({
            vertical: 4,
        }),
    });

    const invalidField = style("invalidField", {}); // To be implemented by each input

    const colorErrorMessage = style("colorErrorMessage", {
        width: percent(100),
        display: "block",
        ...fonts(vars.errorMessage.fonts),
        ...margins({
            vertical: 4,
        }),
        paddingRight: percent(27),
        textAlign: "right",
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
        invalidField,
        colorErrorMessage,
    };
});
