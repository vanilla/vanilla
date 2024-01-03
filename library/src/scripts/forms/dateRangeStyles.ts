/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { css } from "@emotion/css";
import { Mixins } from "@library/styles/Mixins";
import { globalVariables } from "@library/styles/globalStyleVars";
import { useThemeCache } from "@library/styles/themeCache";

export const dateRangeClasses = useThemeCache(() => {
    const globalVars = globalVariables();

    const input = css({
        width: "136px",
        maxWidth: "100%",
    });

    const root = css({
        display: "block",
        position: "relative",
        width: "100%",
    });

    const boundary = css({
        display: "flex",
        flexWrap: "wrap",
        alignItems: "center",
        justifyContent: "space-between",
        width: "100%",
        ...{
            "& + &": {
                ...Mixins.margin({ top: 12 }),
            },
        },
    });

    const label = css({
        overflow: "hidden",
        fontWeight: globalVars.fonts.weights.semiBold,
        wordBreak: "break-word",
        textOverflow: "ellipsis",
        maxWidth: "100%",

        ...Mixins.padding({ left: 8 }),
    });

    return {
        root,
        boundary,
        label,
        input,
    };
});
