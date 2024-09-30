/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css } from "@emotion/css";
import { Mixins } from "@library/styles/Mixins";
import { globalVariables } from "@library/styles/globalStyleVars";
import { useThemeCache } from "@library/styles/themeCache";

const ThreadItemClasses = useThemeCache((headerHasUserPhoto = false) => {
    const globalVars = globalVariables();

    const userContent = css({
        ...Mixins.padding({
            top: headerHasUserPhoto ? 12 : 2,
        }),
    });

    const resultWrapper = css({ display: "flex", gap: 12 });

    const attachmentsContentWrapper = css({
        ...Mixins.margin({ top: globalVars.gutter.size }),
        "&:empty": {
            display: "none",
        },
    });

    const footerWrapper = css({
        display: "flex",
        justifyContent: "space-between",
        alignItems: "center",
        marginInlineEnd: 10,
    });

    return {
        userContent,
        resultWrapper,
        attachmentsContentWrapper,
        footerWrapper,
    };
});

export default ThreadItemClasses;
