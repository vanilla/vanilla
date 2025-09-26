import { css } from "@emotion/css";
import { inputMixin } from "@library/forms/inputStyles";
import { colorDefinition } from "@library/layout/bodyStyles";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { ColorVar } from "@library/styles/CssVar";
import { globalVariables } from "@library/styles/globalStyleVars";
import { singleBorder } from "@library/styles/styleHelpersBorders";
import { useThemeCache } from "@library/styles/styleUtils";
import { Variables } from "@library/styles/Variables";

export const fragmentEditorClasses = useThemeCache(() => {
    const globalVars = globalVariables();

    return {
        // No need already defined
        lightVars: css({}),
        darkVars: css(colorDefinition("dark")),
        container: css({
            padding: "12px 24px",
        }),
        // Special classes for supporting dark mode.
        backButton: css({
            display: "flex",
            alignItems: "center",
        }),
        titleGroup: css({
            display: "flex",
            alignItems: "center",
            gap: 8,
            position: "absolute",
            top: "50%",
            left: "50%",
            transform: "translate(-50%, -50%)",
        }),
        titleSep: css({
            "@media (max-width: 800px)": {
                display: "none",
            },
        }),
        titleType: css({
            "@media (max-width: 800px)": {
                display: "none",
            },
        }),
        titleValue: css({
            display: "flex",
            alignItems: "center",
            gap: 8,
        }),
        titleInput: css({
            ...inputMixin(),
            fontFamily: globalVars.fonts.families.monospace,
            width: "auto",
            paddingTop: 0,
            paddingBottom: 0,
            paddingRight: 6,
        }),

        root: css({
            paddingTop: 48,
            display: "flex",
            flexDirection: "column",
            height: "100vh",
            width: "100vw",
            background: ColorsUtils.var(ColorVar.Background),
            color: ColorsUtils.var(ColorVar.Foreground),
        }),
        titleBar: css({
            position: "fixed",
            height: 48,
            top: 0,
            background: ColorsUtils.var(ColorVar.Background2),
            padding: "0 24px",
            borderBottom: `1px solid ${ColorsUtils.var(ColorVar.Border)}`,
            width: "100%",
            display: "flex",
            alignItems: "center",
            zIndex: 10,
        }),
        // Add new overlay class
        dragOverlay: css({
            position: "fixed",
            top: 0,
            left: 0,
            right: 0,
            bottom: 0,
            zIndex: 9999,
            cursor: "ew-resize",
        }),
        files: css({
            width: "60%",
            display: "flex",
            flexDirection: "column",
        }),
        docs: css({
            flex: 1,
        }),

        row: css({
            display: "flex",
            flexDirection: "row",
            flex: 1,
            height: "100%",
            maxHeight: "100%",
            overflow: "hidden",
        }),

        tabRoot: css({
            display: "flex",
            flexDirection: "column",
            flex: 1,
            height: "100%",
        }),

        tabButtons: css({
            padding: "12px 24px",
            borderBottom: singleBorder(),
        }),

        textEditor: css({
            backgroundColor: "inherit",
            borderRadius: 0,
            height: "100%",
            ".monaco-editor .overflow-guard": {
                borderRadius: 0,
            },
        }),

        previewContainer: css({
            display: "flex",
            flexDirection: "column",
            flex: 1,
            height: "100%",
        }),
        previewControls: css({
            padding: "12px 24px",
            display: "flex",
            gap: 24,
            alignItems: "center",
            flexWrap: "wrap",
        }),
        previewFrameWrapper: css({
            width: "100%",
            height: "100%",
            flex: 1,
            display: "flex",
            flexDirection: "column",
        }),
        previewFrame: css({
            width: "100%",
            height: "100%",
            flex: 1,
            border: "none",
            background: ColorsUtils.var(ColorVar.Background),
            display: "none",
            "&.isLoaded": {
                display: "block",
            },
        }),
        previewFilter: css({
            border: "none",
        }),
        label: css({
            display: "inline-flex",
            alignItems: "center",
            gap: 12,
            "& > strong": {
                whiteSpace: "nowrap",
            },

            "& > em": {
                fontStyle: "italic",
                fontWeight: 400,
            },
        }),
        monacoErrors: css({}),
        errorRow: css({
            display: "flex",
            alignItems: "center",
            gap: 8,
            padding: "8px",
            borderBottom: singleBorder(),
            fontFamily: globalVars.fonts.families.monospace,
            fontSize: 12,
            "&:last-child": {
                borderBottom: 0,
            },
        }),
        errorText: css({ flex: 1 }),
        errorIndicator: css({
            color: ColorsUtils.var(ColorVar.Red),
        }),
    };
});
