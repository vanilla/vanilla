/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { useThemeCache } from "@library/styles/themeCache";

export const emailSettingsClasses = useThemeCache(() => {
    const root = css({
        "& span[id^='errorMessages']": {
            scrollMarginTop: 200,
        },
    });
    const section = css({
        scrollMarginTop: 96,
    });

    const quickLinks = css({
        display: "flex",
        flexDirection: "column",
        color: ColorsUtils.colorOut(globalVariables().links.colors.default),
    });

    const uppercase = css({
        textTransform: "uppercase",
    });

    return { root, section, quickLinks, uppercase };
});
