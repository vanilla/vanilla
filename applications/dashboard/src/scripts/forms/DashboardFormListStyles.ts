/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useThemeCache, styleFactory } from "@library/styles/styleUtils";

export const dashboardFormListClasses = useThemeCache(() => {
    const style = styleFactory("dashboardFormListClasses");

    const root = style({
        padding: 0,
    });

    return {
        root,
    };
});
