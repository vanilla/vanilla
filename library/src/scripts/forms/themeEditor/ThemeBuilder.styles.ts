/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { color, percent } from "csx";
import { negativeUnit, flexHelper, importantUnit } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { defaultFontFamily, globalVariables } from "@library/styles/globalStyleVars";
import { inputVariables } from "@library/forms/inputStyles";
import { toolTipClasses } from "@library/toolTip/toolTipStyles";
import { Mixins } from "@library/styles/Mixins";
import { Variables } from "@library/styles/Variables";

export const themeBuilderVariables = () => {
    const inputVars = inputVariables();
    // Intentionally not overwritable with theming system.
    const fontFamily = [defaultFontFamily];

    const mainColors = {
        primary: color("#0291db"),
        fg: color("#48576a"),
        bg: color("#f5f6f7"),
    };

    const outline = {
        color: mainColors.primary,
    };

    const defaultFont = Variables.font({
        color: mainColors.fg,
        family: fontFamily,
    });

    const label = {
        width: 160,
        fonts: Variables.font({
            ...defaultFont,
            size: 13,
            weight: 600,
        }),
    };
    const title = {
        fonts: Variables.font({
            ...defaultFont,
            size: 16,
            weight: 700,
            transform: "uppercase",
        }),
    };
    const sectionTitle = {
        fonts: Variables.font({
            family: fontFamily,
            color: color("#757e8c"),
            size: 12,
            transform: "uppercase",
            weight: 600,
        }),
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
        fonts: Variables.font({
            family: fontFamily,
            color: color("#d0021b"),
            size: 12,
            weight: 600,
        }),
    };
    const border = {
        ...inputVars.border,
        radius: 3,
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
        fonts: Variables.font({
            ...defaultFont,
            size: 14,
            lineHeight: 1.5,
        }),
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
    const globalVars = globalVariables();

    const root = style({
        display: "flex",
        flexDirection: "column",
        height: "100%",
        maxWidth: styleUnit(themeEditorVariables().panel.width),
        backgroundColor: ColorsUtils.colorOut(vars.panel.bg),
        minHeight: percent(100),
        maxHeight: percent(100),
        overflow: "auto",
        ...Mixins.font(vars.defaultFont),
        ...Mixins.padding({
            vertical: styleUnit(vars.panel.padding),
        }),
    });

    const panelGroup = style("panelGroup", {
        flex: 1,
    });

    const block = style("block", {
        display: "flex",
        alignItems: "flex-start",
        justifyContent: "space-between",
        marginBottom: styleUnit(8),
        ...Mixins.padding({
            horizontal: styleUnit(vars.panel.padding),
        }),
        ...{
            "&.checkBoxBlock + .checkBoxBlock": {
                marginTop: negativeUnit(8),
            },
        },
    });

    const label = style("label", {
        width: styleUnit(vars.label.width),
        display: "flex",
        alignItems: "center",
        flexBasis: styleUnit(vars.label.width),
        flexGrow: 1,
        fontWeight: 600,
        height: styleUnit(vars.input.height),
        ...Mixins.font(vars.label.fonts),
    });

    const undoWrap = style("undoWrap", {
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        width: styleUnit(24),
        height: styleUnit(vars.input.height),
        flexBasis: styleUnit(24),
    });

    const inputWrap = style("inputWrap", {
        display: "flex",
        flexWrap: "wrap",
        alignItems: "stretch",
        justifyContent: "flex-end",
        width: styleUnit(vars.input.width),
        flexBasis: styleUnit(vars.input.width),
        position: "relative",
    });

    const checkBox = style("checkBox", {
        marginRight: importantUnit(0),
    });

    const checkBoxWrap = style("checkBoxWrap", {
        display: "flex",
        flexWrap: "wrap",
        alignItems: "stretch",
        justifyContent: "flex-start",
        width: styleUnit(vars.input.width),
        flexBasis: styleUnit(vars.input.width),
        ...Mixins.font(vars.input.fonts),
        fontWeight: 600,
        height: vars.input.height,
        paddingBottom: 4,
    });

    const title = style("title", {
        ...Mixins.font(vars.title.fonts),
        textAlign: "center",
        marginBottom: styleUnit(20),
        ...Mixins.padding({
            horizontal: styleUnit(vars.panel.padding),
        }),
        marginTop: styleUnit(20),
        paddingTop: styleUnit(20),
        borderTop: `solid #c1cbd7 1px`,
        ...{
            "&:first-child": {
                marginTop: 0,
                paddingTop: 0,
                borderTop: "none",
            },
        },
    });

    const section = style("section", {
        borderTop: `solid #c1cbd7 1px`,
        marginTop: styleUnit(32),
    });

    const sectionTitle = style("sectionTitle", {
        ...Mixins.font(vars.sectionTitle.fonts),
        textAlign: "center",
        ...Mixins.margin({
            top: 6,
            bottom: 14,
        }),
        ...Mixins.padding({
            horizontal: styleUnit(vars.panel.padding),
        }),
    });

    const subGroupSection = style("subGroupSection", {});
    const subGroupSectionTitle = style("subGroupSectionTitle", {
        ...Mixins.margin({
            top: 20,
            bottom: 12,
        }),
        ...Mixins.padding({
            horizontal: styleUnit(vars.panel.padding),
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
        ...Mixins.font(vars.errorMessage.fonts),
        ...Mixins.margin({
            vertical: 4,
        }),
    });

    const invalidField = style("invalidField", {}); // To be implemented by each input

    const colorErrorMessage = style("colorErrorMessage", {
        width: percent(100),
        display: "block",
        ...Mixins.font(vars.errorMessage.fonts),
        ...Mixins.margin({
            vertical: 4,
        }),
        paddingRight: percent(27),
        textAlign: "right",
    });

    const blockInfo = style("blockInfo", {
        ...flexHelper().middle(),
        marginLeft: globalVars.gutter.half,
        ...{
            "&:hover": {
                color: ColorsUtils.colorOut(globalVars.mainColors.primary),
            },
        },
    });

    const resetButton = style("resetButton", {
        height: 24,
        width: 24,
        position: "absolute",
        right: percent(100),
        top: 0,
        bottom: 0,
        marginTop: "auto",
        marginBottom: "auto",
    });

    const documentationIconLink = style("documentationIconLink", {
        ...Mixins.clickable.itemState({ skipDefault: true }),
        color: "inherit",
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
    });

    const docBlockTextContainer = style("docBlockTextContainer", {
        display: "block",
        ...Mixins.font({
            size: 12,
            color: globalVars.meta.text.color,
            lineHeight: globalVars.meta.text.lineHeight,
        }),
        padding: 0,
        ...Mixins.margin({
            top: styleUnit(-3),
            bottom: 8,
        }),
    });

    const small = style("small", {
        ...{
            [`.${toolTipClasses().noPointerTrigger}`]: {
                minWidth: 20,
                minHeight: 20,
            },
        },
    });

    const iconLink = style("iconLink", {
        marginLeft: styleUnit(8),
    });

    return {
        root,
        panelGroup,
        block,
        label,
        undoWrap,
        inputWrap,
        checkBox,
        checkBoxWrap,
        title,
        section,
        sectionTitle,
        error,
        errorContainer,
        subGroupSection,
        subGroupSectionTitle,
        invalidField,
        colorErrorMessage,
        blockInfo,
        resetButton,
        documentationIconLink,
        docBlockTextContainer,
        small,
        iconLink,
    };
});
