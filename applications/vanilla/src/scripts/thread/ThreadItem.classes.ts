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
    });

    return {
        userContent,
        resultWrapper,
        attachmentsContentWrapper,
    };
});

export default ThreadItemClasses;
