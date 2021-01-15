/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { percent } from "csx";
import { styleUnit } from "@library/styles/styleUnit";
import { layoutVariables } from "@library/layout/panelLayoutStyles";

export const twitterEmbedClasses = useThemeCache(() => {
    const style = styleFactory("twitter");
    const mediaQueries = layoutVariables().mediaQueries();

    const card = style(
        "card",
        {
            display: "inline-flex",
            minWidth: styleUnit(554),
            maxWidth: percent(100),
        },
        mediaQueries.oneColumnDown({
            minWidth: "auto",
        }),
    );

    return { card };
});
