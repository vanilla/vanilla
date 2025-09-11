/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css } from "@emotion/css";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { useThemeCache } from "@library/styles/themeCache";
import { percent } from "csx";

export const listItemMediaClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const mediaItem = css({
        background: ColorsUtils.colorOut(globalVars.mixPrimaryAndBg(0.25)),
    });

    const ratioContainer = useThemeCache((ratio: { vertical: number; horizontal: number }) => {
        return css({
            position: "relative",
            width: "auto",
            paddingTop: percent((ratio.vertical / ratio.horizontal) * 100),
        });
    });

    const naturalRatioContainer = useThemeCache((ratio: { vertical: number; horizontal: number }) => {
        return css({
            position: "relative",
            aspectRatio: `${ratio.horizontal} / ${ratio.vertical}`,
        });
    });

    const fullParent = css({
        position: "absolute",
        top: 0,
        left: 0,
        right: 0,
        bottom: 0,
        height: "100%",
        width: "100%",
    });

    const coverImage = css({
        objectFit: "cover",
        position: "absolute",
        top: 0,
        left: 0,
        right: 0,
        bottom: 0,
        height: "100%",
        width: "100%",
    });

    return {
        mediaItem,
        ratioContainer,
        naturalRatioContainer,
        fullParent,
        coverImage,
    };
});
