/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css } from "@emotion/css";
import { useThemeCache } from "@library/styles/themeCache";

export const ThreadItemPermalinkClasses = useThemeCache(() => {
    const copyLinkButton = css({
        marginLeft: 0,
    });

    return {
        copyLinkButton,
    };
});
