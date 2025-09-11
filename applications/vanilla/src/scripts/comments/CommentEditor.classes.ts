/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { useThemeCache } from "@library/styles/themeCache";

export const commentEditorClasses = useThemeCache(() => {
    const editorPostActions = css({
        display: "flex",
        alignItems: "center",
        justifyContent: "flex-end",
        width: "100%",
        gap: 12,
        marginTop: 12,
        flexWrap: "wrap",
    });

    const draftMessage = css({
        marginRight: "auto",
        "@media (max-width: 600px)": {
            marginRight: "auto",
            marginLeft: "auto",
            flexBasis: "100%",
            textAlign: "end",
        },
    });

    const draftIndicator = css({
        width: 24,
        height: "100%",
        marginInlineEnd: 8,
        transform: "translateY(2px)",
    });

    const previewDisablePointerEvents = css({
        pointerEvents: "none",
    });

    const draftHeaderWrapper = css({
        display: "flex",
        alignItems: "baseline",
        justifyContent: "space-between",
        flexDirection: "column",
    });

    const errorMessages = css({
        marginBlockStart: 0,
        marginBlockEnd: 16,
    });

    const title = css({
        flexShrink: 0,
        marginBlockEnd: 0,
    });

    const formatNoticeLayout = css({
        marginBlockEnd: 12,
    });

    const editorSpacing = css({
        marginBlockStart: 12,
    });

    return {
        editorPostActions,
        draftMessage,
        draftIndicator,
        previewDisablePointerEvents,
        draftHeaderWrapper,
        title,
        formatNoticeLayout,
        editorSpacing,
        errorMessages,
    };
});
