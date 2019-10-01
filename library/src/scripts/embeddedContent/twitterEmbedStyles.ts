/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { useThemeCache, styleFactory } from "@library/styles/styleUtils";
import { margins } from "@library/styles/styleHelpers";
import { important, percent } from "csx";

export const twitterEmbedClasses = useThemeCache(() => {
    const style = styleFactory("twitter");

    const card = style("card", {
        // borderRadius: 4,
        width: "auto",
        display: "inline-flex",
        maxWidth: percent(100),

        $nest: {
            // The shadow DOM root.
            "& .twitter-tweet": {
                ...margins({
                    vertical: important(0),
                    horizontal: important("auto"),
                }),
                minWidth: important("100%"),
            },
        },
    });

    return { card };
});
