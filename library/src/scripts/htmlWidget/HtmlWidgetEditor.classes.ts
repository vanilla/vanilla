/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css } from "@emotion/css";
import { Mixins } from "@library/styles/Mixins";
import { useThemeCache } from "@library/styles/styleUtils";
import { calc } from "csx";

export const htmlWidgetEditorClasses = useThemeCache(() => {
    const editor = css({
        height: "100%",
        border: "none",
        borderRadius: 0,
    });

    const tabsRoot = css({
        height: "100%",
    });

    const previewIcon = css({
        height: 80,
        width: 80,
    });

    return { editor, tabsRoot, previewIcon };
});
