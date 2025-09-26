/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css } from "@emotion/css";
import { useThemeCache } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";

export const draftFormFooterContentClasses = useThemeCache(() => {
    const globalVars = globalVariables();

    const footer = css({
        display: "flex",
        justifyContent: "flex-end",
        gap: 16,
        flexWrap: "wrap",
    });

    const draftLastSaved = css({
        ...Mixins.margin({
            vertical: globalVars.spacer.componentInner,
        }),
        display: "flex",
        justifyContent: "end",
        "& time": {
            marginInlineStart: ".5ch",
        },
    });

    return {
        footer,
        draftLastSaved,
    };
});
