/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { Mixins } from "@library/styles/Mixins";
import { globalVariables } from "@library/styles/globalStyleVars";
import { useThemeCache } from "@library/styles/themeCache";

export const notificationPreferencesFormClasses = useThemeCache(() => {
    const globalVars = globalVariables();

    const subgroupWrapper = css({
        ...Mixins.margin({
            vertical: globalVars.spacer.componentInner,
        }),
    });

    const subgroupHeading = css({
        ...Mixins.font({
            weight: globalVars.fonts.weights.semiBold,
        }),
    });

    const description = css({
        ...Mixins.margin({
            top: globalVars.gutter.quarter,
        }),
    });

    const tableWrapper = css({
        ...Mixins.margin({
            top: globalVars.gutter.half,
        }),
    });

    const tableHeader = css({
        "& + &": {
            ...Mixins.padding({
                left: globalVars.gutter.quarter,
            }),
        },
    });

    const checkbox = css({
        ...Mixins.padding({ vertical: 0 }),
    });

    const tableRow = css({
        ...Mixins.font({
            weight: globalVars.fonts.weights.semiBold,
        }),
    });

    const tableCell = css({
        verticalAlign: "middle",
        ...Mixins.padding({
            top: globalVars.gutter.quarter,
        }),

        "& + &": {
            ...Mixins.padding({
                left: globalVars.gutter.quarter,
            }),
        },
    });

    const tableDescriptionWrapper = css({
        ...Mixins.margin({ left: globalVars.gutter.quarter }),
        display: "flex",
    });

    return {
        description,
        subgroupWrapper,
        subgroupHeading,
        tableWrapper,
        tableHeader,
        checkbox,
        tableRow,
        tableCell,
        tableDescriptionWrapper,
    };
});
