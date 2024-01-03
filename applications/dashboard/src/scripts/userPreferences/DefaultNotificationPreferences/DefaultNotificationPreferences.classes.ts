/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { Mixins } from "@library/styles/Mixins";
import { globalVariables } from "@library/styles/globalStyleVars";
import { useThemeCache } from "@library/styles/themeCache";

export const defaultNotificationPreferencesFormClasses = useThemeCache(() => {
    const globalVars = globalVariables();

    const tab = css({
        ...Mixins.font({
            ...globalVars.fontSizeAndWeightVars("medium", "bold"),
            lineHeight: 20 / 14,
        }),
    });

    const sectionHeading = css({
        ...Mixins.font({
            ...globalVars.fontSizeAndWeightVars("large", "semiBold"),
            lineHeight: globalVars.lineHeights.base,
        }),
    });

    return {
        tab,
        sectionHeading,
    };
});
