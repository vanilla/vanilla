/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css } from "@emotion/css";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { ColorVar } from "@library/styles/CssVar";
import { singleBorder } from "@library/styles/styleHelpersBorders";
import { useThemeCache } from "@library/styles/styleUtils";

export const fragmentEditorDiffViewerClasses = useThemeCache(() => ({
    frameBody: css({ height: "100%" }),
    root: css({
        height: "100%",
        display: "flex",
        flexDirection: "row",
    }),
    changedFiles: css({
        width: "20%",
        display: "flex",
        flexDirection: "column",
    }),
    changeListHeading: css({
        padding: "12px 16px",
        fontSize: 14,
        fontWeight: 600,
    }),
    title: css({}),
    revisionGroup: css({
        display: "flex",
        alignItems: "center",
        gap: 16,
        padding: "4px 16px",
    }),
    revisionItem: css({
        padding: "8px 12px",
        border: singleBorder(),
        borderRadius: 6,
    }),
    revisionName: css({
        fontSize: 12,
        display: "inline-block",
        maxWidth: 240,
        overflow: "hidden",
        textOverflow: "ellipsis",
        whiteSpace: "nowrap",
    }),
    revertForm: css({
        display: "flex",
        flexDirection: "column",
        borderTop: singleBorder(),
        padding: 16,

        "& button": {
            marginTop: 8,
            width: "100%",
        },
    }),

    emptyMessage: css({
        height: "100%",
        display: "flex",
        flexDirection: "column",
        alignItems: "center",
        justifyContent: "center",
    }),
    changedFilesList: css({}),
    changedFileButton: css({
        width: "100%",
        padding: "8px 16px",
        borderTop: singleBorder(),
        "&:last-child": {
            borderBottom: singleBorder(),
        },

        "&:hover, &:focus, &:active": {
            background: ColorsUtils.var(ColorVar.Background1),
            outline: "none",
        },
        "&[data-state='active']": {
            background: ColorsUtils.var(ColorVar.PrimaryState),
            color: ColorsUtils.var(ColorVar.PrimaryContrast),
        },
    }),
    changedFileButtonContents: css({
        display: "flex",
        justifyContent: "space-between",
        alignItems: "center",
    }),
    changeFileName: css({
        flex: 1,
        whiteSpace: "nowrap",
        textOverflow: "ellipsis",
        textAlign: "left",
        minWidth: 0,
        overflow: "hidden",
        paddingRight: 8,
    }),
    diffViewer: css({
        flex: 1,
        height: "100%",
        padding: 12,
    }),
    diffContent: css({
        height: "100%",
        display: "none",
        "&[data-state='active']": {
            display: "block",
        },
    }),
    diffHeader: css({
        marginBottom: 16,
        marginLeft: 8,
        marginRight: 8,
    }),
    plainTextDiff: css({
        whiteSpace: "pre-wrap",
        border: singleBorder(),
        padding: 16,
        borderRadius: 6,
        marginBottom: 24,
        marginLeft: 8,
        marginRight: 8,
    }),
    commitForm: css({
        marginBottom: 16,
    }),
    commitWarningContainer: css({
        padding: "0 16px",
        marginBottom: 8,
    }),
    commitWarning: css({
        borderRadius: 6,
    }),
    fileViewer: css({
        padding: "0 8px",
    }),
    fileError: css({
        // Get it to line up with the status indicators.
        marginTop: -2,
        marginRight: -4,
        height: 20,

        // Coloring
        color: ColorsUtils.var(ColorVar.Red),
        "[data-state='active'] &": {
            color: ColorsUtils.var(ColorVar.PrimaryContrast),
        },
    }),
    editor: css({}),
}));
