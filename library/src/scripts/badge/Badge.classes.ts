/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { useThemeCache } from "@library/styles/themeCache";
import { css } from "@emotion/css";
import { Mixins } from "@library/styles/Mixins";
import { getPixelNumber } from "@library/styles/styleUtils";
import { badgesVariables } from "@library/badge/Badge.variables";

export const badgeListClasses = useThemeCache(() => {
    const badgeVars = badgesVariables();

    const list = css({
        display: "flex",
        flexWrap: "wrap",
    });

    const listItem = css({
        ...Mixins.margin({
            right: getPixelNumber(badgeVars.spacing.horizontal),
            bottom: getPixelNumber(badgeVars.spacing.vertical),
        }),
    });

    return {
        list,
        listItem,
    };
});

export const badgeClasses = useThemeCache(() => {
    const badgeVars = badgesVariables();

    const link = css({});

    const itemHasCount = css({
        position: "relative",
        width: badgeVars.sizing.width,
        height: badgeVars.sizing.width,
    });
    const count = css({
        top: 0,
        left: 27,
        right: "unset",
        height: 18,
        backgroundColor: badgeVars.colors.count.background,
        ...Mixins.border({
            color: badgeVars.colors.count.borderColor,
            radius: 8,
            width: 1.3,
        }),
        ...Mixins.font({
            size: badgeVars.fonts.count.size,
        }),
    });

    const image = css({
        width: badgeVars.sizing.width,
        height: badgeVars.sizing.width,
    });

    return {
        link,
        image,
        itemHasCount,
        count,
    };
});
