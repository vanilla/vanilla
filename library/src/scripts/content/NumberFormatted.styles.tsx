/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";

export const numberFormattedClasses = useThemeCache(() => {
    const style = styleFactory("numberFormatter");

    const root = style({
        textDecoration: "inherit",
    });

    return { root };
});
