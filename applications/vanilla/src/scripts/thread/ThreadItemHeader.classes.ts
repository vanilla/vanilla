/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { css } from "@emotion/css";
import { userPhotoVariables } from "@library/headers/mebox/pieces/userPhotoStyles";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { useThemeCache } from "@library/styles/themeCache";

export const threadItemHeaderClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const userPhotoVars = userPhotoVariables();

    const photoSize = userPhotoVars.sizing.medium;

    const root = css({
        display: "flex",
        flexWrap: "nowrap",
        alignItems: "center",
        justifyContent: "space-between",
        width: "100%",
        minHeight: photoSize,
    });

    const main = css({
        display: "flex",
        flexDirection: "column",
        flexWrap: "nowrap",
        alignItems: "flex-start",
        justifyContent: "space-between",
        width: "100%",
        paddingLeft: 8,
        minHeight: photoSize,
    });

    const userName = css({
        "&&&": {
            ...Mixins.font({
                ...globalVars.fontSizeAndWeightVars("medium", "bold"),
                color: ColorsUtils.colorOut(globalVars.mainColors.fgHeading),
                lineHeight: globalVars.lineHeights.condensed,
            }),
        },
    });

    const rankLabel = css({
        ...Mixins.padding({
            horizontal: 10,
        }),
        ...Mixins.font({
            color: globalVars.mainColors.primary,
            size: 10,
            lineHeight: 15 / 10,
            transform: "uppercase",
        }),
        ...Mixins.border({
            color: globalVars.mainColors.primary,
            radius: 3,
        }),

        // Without these it won't align in a meta-row (and will actually push things below down by a few pixels).
        display: "inline",
        verticalAlign: "middle",

        // Looks slightly offset without this.
        position: "relative",
        top: "-1px",
    });

    const headerMeta = css({
        display: "flex",
        alignItems: "start",
        justifyContent: "start",
        gap: 4,

        "& > div": {
            margin: "inherit",
        },

        "& svg": {
            color: "currentColor",
        },

        "@container threadItemContainer (width < 500px)": {
            gap: 12,
        },
    });

    return {
        root,
        rankLabel,
        userName,
        headerMeta,
        main,
    };
});
