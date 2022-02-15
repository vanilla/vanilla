/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useThemeCache } from "@library/styles/styleUtils";
import { media } from "@library/styles/styleShim";
import { css } from "@emotion/css";

const thumbnailGridClasses = useThemeCache(() => {
    const grid = css(
        {
            display: "grid",
            justifyItems: "stretch",
            alignItems: "stretch",
            gridTemplateColumns: "repeat(3, 1fr)",
            gridAutoRows: "minmax(240px, auto)",
            gridGap: 20,
        },
        media(
            { maxWidth: 1300 },
            {
                gridTemplateColumns: "repeat(2, 1fr)",
            },
        ),
        media(
            { maxWidth: 600 },
            {
                gridTemplateColumns: "repeat(1, 1fr)",
            },
        ),
    );

    const gridItem = css({
        flex: 1,
        display: "flex",
        flexDirection: "column",
    });

    return { grid, gridItem };
});

export default thumbnailGridClasses;
