/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { useThemeCache } from "@library/styles/themeCache";
import { noticeVariables } from "@library/metas/Notice.variables";
import { css } from "@emotion/css";
import { Mixins } from "@library/styles/Mixins";

export const noticeClasses = useThemeCache(() => {
    const { font, border, spacing } = noticeVariables();

    const root = css({
        ...Mixins.font(font),
        ...Mixins.border(border),
        ...Mixins.padding(spacing),
    });

    return {
        root,
    };
});
