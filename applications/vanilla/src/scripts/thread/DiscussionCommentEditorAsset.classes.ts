/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { globalVariables } from "@library/styles/globalStyleVars";
import { useThemeCache } from "@library/styles/themeCache";

export const discussionCommentEditorClasses = useThemeCache(() => {
    const globalVars = globalVariables();

    const pageBox = css({
        marginTop: globalVars.spacer.pageComponentCompact,
    });

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

    return {
        pageBox,
        editorPostActions,
        draftMessage,
        draftIndicator,
        previewDisablePointerEvents,
    };
});
