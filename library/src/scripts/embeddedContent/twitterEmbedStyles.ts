/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { useThemeCache, styleFactory } from "@library/styles/styleUtils";
import { percent } from "csx";

export const twitterEmbedClasses = useThemeCache(() => {
    const style = styleFactory("twitter");

    const card = style("card", {
        width: "auto",
        display: "inline-flex",
        maxWidth: percent(100),
    });

    return { card };
});
