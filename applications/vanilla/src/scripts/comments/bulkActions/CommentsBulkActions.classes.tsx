/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css } from "@emotion/css";
import { useThemeCache } from "@library/styles/styleUtils";

export const commentsBulkActions = useThemeCache(() => {
    const modalHeader = css({
        marginBottom: 16,
    });

    const topLevelError = css({
        marginTop: 8,
        marginBottom: 16,
    });

    return {
        modalHeader,
        topLevelError,
    };
});
