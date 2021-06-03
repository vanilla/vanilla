/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory, useThemeCache } from "@library/styles/styleUtils";
import { media } from "@library/styles/styleShim";

const thumbnailGridClasses = useThemeCache(() => {
    const style = styleFactory("thumbnailGrid");

    const grid = style(
        "grid",
        {
            display: ["flex", "grid"],
            flexWrap: "wrap",
            justifyContent: "flex-start",
            marginTop: -18,
            paddingRight: 18,
            paddingLeft: 18,
            marginLeft: -36,
            marginRight: -36,
            gridTemplateColumns: "repeat(3, 1fr)",
            gridAutoRows: "minmax(240px, auto)",
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

    const gridItem = style("gridItem", {
        flex: 1,
        paddingLeft: 18,
        paddingRight: 18,
        paddingTop: 36,
        display: "flex",
        flexDirection: "column",
    });

    return { grid, gridItem };
});

export default thumbnailGridClasses;
