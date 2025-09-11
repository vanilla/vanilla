/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { useThemeCache } from "@library/styles/themeCache";

export const customPagesClasses = useThemeCache(() => {
    const pageList = css({
        padding: 16,
    });
    return { pageList };
});
